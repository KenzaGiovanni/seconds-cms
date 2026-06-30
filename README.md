# Seconds CMS

A Laravel-native CMS that runs as a **plain content site** or, with a single toggle, as a **full ecommerce store**. Think "WordPress + WooCommerce", but built deliberately on Laravel instead of bolted together - with installable, backend-editable themes and first-class Indonesian commerce (Xendit payments, KiriminAja delivery).

Built by Kenza under &Now. Target users: &Now consulting clients (Indonesian SMEs) first, productizable later.

> **Single source of truth:** product and technical decisions live in [`../seconds-spec.md`](../seconds-spec.md). Build state and decisions are logged in [`../MEMORY.md`](../MEMORY.md). Read those before doing build work.

---

## What it does

- **One codebase, two modes.** Content CMS by default; flip the `ecommerce` setting to unlock catalog, cart, checkout, orders, payments, and delivery.
- **Installable themes.** WordPress-style lifecycle - install from a zip, activate (one active theme at a time), uninstall. Themes are server-rendered Blade packages, editable from the backend.
- **Two-tier theme editing.** Clients customize via safe settings + content blocks. Raw Blade editing is gated to `developer` / `super-admin` roles only.
- **Role-based access.** Four roles (`super-admin`, `developer`, `admin`, `editor`) via spatie/laravel-permission.
- **Indonesian commerce.** Xendit (VA, QRIS, e-wallets, cards) and KiriminAja (live rates, booking, tracking) - both behind swappable internal interfaces.

## Stack

| Layer | Choice |
|---|---|
| Backend | Laravel 13 (monolith), PHP 8.4 |
| Admin UI | Livewire 4 + Blade |
| Themes | Blade-based, server-rendered, installable + activatable |
| RBAC | spatie/laravel-permission 8 |
| Database | MySQL 9 |
| Tests | Pest 4 |
| Formatting | Laravel Pint |
| Payments | Xendit (`xendit/xendit-php`) - Phase 3 |
| Delivery | KiriminAja (`kiriminaja/php`) - Phase 4 |

## Requirements

- PHP 8.4+
- Composer 2
- MySQL 9 (or compatible)
- Node 20+ / npm

## Getting started

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Configure your database in .env, then create the schema
#    Default dev DB: `seconds` (root / no password)
php artisan migrate

# 4. Build front-end assets
npm run build
```

### Two ways to set up the admin user

**Option A - the installer (mirrors a real first-run).** Start the server and visit `/install`. It runs migrations, seeds roles + settings, installs and activates the default theme, and creates your super-admin. The installer is only reachable while no users exist.

```bash
php artisan serve
# visit http://localhost:8000/install
```

**Option B - seed a dev admin.** For local development, seed a ready-made super-admin:

```bash
php artisan db:seed
# login: admin@seconds.test / password
```

Then sign in at `/admin/login`.

## Day-to-day

```bash
php artisan serve        # run the app
npm run dev              # vite dev server (hot reload)
php artisan test         # run the Pest suite
./vendor/bin/pint        # format (run before committing)
php artisan db:seed      # reseed roles, settings, dev admin
```

## The ecommerce toggle

Ecommerce is off by default. It is a settings-backed feature flag, read through the `App\Support\Feature` helper and enforced by the `ecommerce` route middleware:

```php
use App\Support\Feature;

if (Feature::ecommerce()) {
    // shop surfaces are live
}
```

Flip it by setting the `ecommerce` value to `true` in the `settings` table (admin UI lands in a later phase). When off, ecommerce routes 404 and the shop nav is hidden.

## Themes

A theme is a folder under `themes/<slug>/`:

```
themes/default/
  theme.json        # manifest: slug, name, version, supports, settings schema
  views/            # Blade templates
  assets/           # css / js / images
```

The active theme's views are registered under the `theme::` Blade namespace by `App\Support\ThemeManager`. The default theme is always registered as the base/fallback and the active theme is prepended so its templates override it. Exactly one theme is active at a time; activating one deactivates the rest in a transaction, and the active theme cannot be uninstalled. A theme's effective settings are resolved by `App\Support\ThemeSettings` - `theme.json` defaults merged with stored overrides.

## Content & front-end rendering

Pages and posts share one `contents` table, distinguished by a `type` discriminator (STI-lite). Query type-scoped via the `Page` / `Post` models, or cross-type via the `Content` base model. Content is `draft` / `published` / `scheduled`; the `published()` scope gates on status **and** publish time.

The front-end is a thin pipeline: `GET /` renders the theme's `home` template, and a single-segment catch-all `GET /{slug}` resolves a published content item and renders `theme::{type}` (`page` / `post`) with the content, its rendered blocks, and theme settings. Drafts, not-yet-published, and unknown slugs return 404.

Rich content is stored as **blocks** - an ordered `blocks` json array of `{ type, data }`. `App\Support\BlockRenderer` maps each block to a `theme::blocks.<type>` partial, falling back safely for unknown types so a bad block never fatals a page.

## Project layout

```
app/
  Enums/             # Role, Permission, ContentStatus
  Http/Controllers/  # FrontController (home + slug rendering)
  Http/Middleware/   # EnsureStaff, EnsureEcommerceEnabled
  Livewire/          # Auth, Dashboard, Installer
  Models/            # User, Setting, Theme, Content (+ Page, Post)
  Support/           # Feature, ThemeManager, ThemeManifest, ThemeSettings, BlockRenderer
database/
  migrations/
  seeders/           # RolesAndPermissions, Settings, DatabaseSeeder
themes/
  default/           # ships pre-installed + active; base layout, page/post/home, block partials
tests/
  Feature/           # Auth, Settings, Themes, Install, Content
```

## Authoring model: theme-defined blocks

Page layout is composed from **blocks**, and blocks are defined by the **theme**, not hardcoded. Each theme ships a `blocks.php` manifest mapping a block `type` to a label + **field schema**, plus a matching `views/blocks/<type>.blade.php` partial:

```php
// themes/default/blocks.php
'hero' => [
    'label' => 'Hero',
    'fields' => [
        ['key' => 'heading', 'type' => 'text', 'label' => 'Heading'],
        ['key' => 'cta_url', 'type' => 'text', 'label' => 'Button URL'],
        // ...
    ],
],
```

The admin **auto-generates the editor form** from that schema (`BlockRegistry` reads the active theme; the page/post editor renders an input per field type). Field types: `text`, `textarea`, `richtext`, `email`, `image`, `number`, `toggle`, `select`, and `repeater` (nested groups - e.g. a feature grid's cards). To add a block: declare it in `blocks.php` and ship a partial of the same name. The default theme ships Hero, Feature grid, Gallery, CTA, Image, Form, Heading, Paragraph, and Divider as the reference set.

## Forms

The **Forms** module builds on the same field-schema engine. Define a form in the admin (fields + notify email + success message), then embed it:

- **`@form('contact')`** Blade directive in a theme template, or
- the **Form block** on any page (enter the form slug).

Submissions POST to `/forms/{slug}`, are validated against the form's schema, pass a honeypot anti-spam check, and are stored as `form_submissions` (viewable in the admin). Email notification is stubbed until mail is configured.

### See it: the sample page

```bash
php artisan db:seed --class=DemoContentSeeder   # creates a /sample page + contact form
```

Visit `/sample` for a page stacked from Hero + Feature grid + CTA + a contact form, rendered through the default theme.

## Build status

Phase 0 (Foundation), Phase 1 (Core CMS), and Phase 1.5 (Block system v2 + Forms) are **complete**. Test suite: **189/189 green**.

What's shipped:
- Auth, RBAC (4 roles via spatie), admin shell, ecommerce toggle, first-run installer
- Content model (pages + posts, STI-lite), publish states, content blocks
- Admin CRUD: pages, posts, media library, menus, forms, theme install/activate/uninstall, theme settings
- **Schema-driven block system**: theme-defined blocks, auto-generated editor forms, repeater fields
- **Forms**: builder, submissions, `@form` directive + Form block, honeypot
- Front-end rendering: home, page, post, blog listing, category + tag archives
- SEO head (OG, canonical, noindex), /sitemap.xml, /robots.txt
- Default theme: full Option B design system + a styled section-block library (Hero, Features, Gallery, CTA)

Roadmap (see the spec for detail):

1. **Phase 1** - Core CMS - **DONE**
2. **Phase 1.5** - Block system v2 + Forms - **DONE**
3. **Phase 2** - Ecommerce core: catalog, cart, checkout, orders.
4. **Phase 3** - Payments (Xendit).
5. **Phase 4** - Delivery (KiriminAja).
6. **Phase 5** - Productization: gated theme code editor, more themes, hardening, docs.

## License

Proprietary - &Now. All rights reserved.
