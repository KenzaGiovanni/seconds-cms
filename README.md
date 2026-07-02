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
| Delivery | KiriminAja (`kiriminaja/kiriminaja-php`) - Phase 4 |

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

Flip it from **Website Settings** (`/admin/settings`, `settings.manage` permission) - a "Shop" section with an "Enable ecommerce" checkbox. When off, `/shop`, `/cart`, `/checkout` all 404 and the Shop section is hidden from the admin sidebar.

### Catalog admin

With ecommerce on, `/admin/shop/products` and `/admin/shop/categories` give staff with the `products.manage` permission full CRUD:

- **Products** - simple (single price/sku/stock) or variable (multiple variants, each with its own price/sku/stock and up to two option pairs like Size/Color). Rich description via the same block-editor engine as pages/posts, featured image, category assignment, and a per-product stock policy (don't track / deny when out / allow backorder).
- **Product categories** - flat or nested (parent/child), assignable to products via checkboxes.
- **Orders** - admin surface exists (`/admin/shop/orders`, `orders.manage` permission) but is a placeholder until checkout ships in Phase 2.4.

### Storefront

`/shop` lists published, in-stock-aware products (grid, optional `?category=slug` filter) and `/shop/{slug}` is the product detail page. Variant selection and stock/price display are handled by an embedded Livewire widget (`App\Livewire\Shop\ProductDetail`) so switching a variant updates price/stock without a full page reload. Both routes 404 when the ecommerce toggle is off or the product isn't published.

The default theme header shows a persistent **Shop** link (next to the mini-cart) whenever ecommerce is on, so the storefront is always reachable even with no admin-configured menu.

### Cart

A db-backed cart (`App\Support\CartManager`) keyed by session for guests and by user once logged in (no merge-on-login yet - a guest cart doesn't currently follow you into an account). Add to cart from the product detail widget (variant-aware, respects each product's stock policy); a header mini-cart badge and the `/cart` page both update live via a `cart-updated` browser event, no full page reload needed to see quantity/total changes.

The `/cart` page is a two-column layout: line items on the left, a sticky **order summary** panel on the right (coupon, subtotal, discount, a "shipping calculated at checkout" line, and the total) with the proceed-to-checkout action.

### Checkout + orders

`/checkout` collects contact + shipping details (guest checkout - no account required) and places the order via `App\Support\CheckoutService`, which snapshots product name/sku/price/options onto each order line (later catalog edits never rewrite past orders), decrements stock, and moves the order to `awaiting_payment`. The customer lands on `/order/{number}` - that confirmation page checks ownership (account match, or a session flag set right after checkout) rather than trusting the order number alone, since order numbers aren't secret.

The checkout page also shows **delivery method** and **payment method** selectors. Delivery is still a **UI-only placeholder** (live courier rates arrive with the delivery module, Phase 4). Payment method now has one live option, **bank transfer** (Phase 3.1) - Xendit's VA/QRIS/e-wallet/card show as "available once activated" until 3.2.

Admins manage orders at `/admin/shop/orders` (`orders.manage` permission): a list with status pills, and a detail screen showing line items, shipping address, totals, and status-transition buttons limited to whatever `OrderStatus::transitions()` allows next. Cancelling an order that was `awaiting_payment` or `paid` automatically restocks its line items.

### Storefront UI ownership (planned split)

Ecommerce is a module ("extension"), not part of any single theme. Its UI is designed in two tiers so swapping themes never breaks a functional flow: **module-owned** (cart, checkout, customer portal, order confirmation - consistent on every theme) vs **theme-owned** (shop index, category, product detail - designed per theme). Today cart/checkout/order-confirmation still physically live in `themes/default/views/`; moving them into module-owned views is a Phase 5 refactor. See `seconds-spec.md` §5.1.

### Customer portal (designed, not built)

A logged-in **customer portal** (`/account`: order history + status, saved addresses, profile) is designed - see the mockup at `design/moodboards/customer-portal.html`. Customers get **real accounts** (public registration/login, separate from staff), with guest checkout still allowed and "create account" offered at the end. It is built as its own phase **after Phase 3 payments**, since order status is only meaningful once payments are real.

### Inventory + order-management polish

The admin product list shows stock **read-only** (edit stock on the product form itself). Both the admin list and the storefront shop grid flag "low stock" / "out of stock" using one shared threshold, `config('seconds.low_stock_threshold')` (env `SECONDS_LOW_STOCK_THRESHOLD`, default 5). Order confirmation and status-change emails are stubbed with a code comment (same convention as the Forms module) until mail is configured - nothing sends yet.

**Stock reservation model.** Placing an order decrements ("reserves") stock immediately, at checkout. Paying keeps it reserved; cancelling an order that was `awaiting_payment` or `paid` returns the stock. This restock-on-cancel lives in `Order::transitionTo()`, so every path that cancels an order - admin action now, payment webhooks/expiry later - restores inventory automatically.

### Promotions & coupons

`/admin/shop/promotions` (`promotions.manage`) manages discounts. A promotion is **automatic** (applied to any qualifying cart) or a **coupon** (needs a code), and discounts **per item** across the whole cart by a **percentage** or a **fixed amount**. Rules: a minimum-items threshold to qualify, a per-order cap on how many items get discounted (extras are full price), a global quota counted in discounted **units**, an active date range, allowed days of the week, and a daily time window (e.g. a 4-8pm happy hour). Coupon promotions hold any number of codes, each with its own redemption limit; codes can be added one at a time or **mass-generated** in a batch (count, optional prefix, per-code use limit).

`App\Support\DiscountCalculator` is the engine: it computes the best single discount for a cart (automatic promos and an entered coupon all compete - **best wins, never stacked**), discounting the highest-priced units first. At checkout the winning promotion row is **locked** while its quota and the coupon's use count are consumed, so the quota isn't oversold under normal concurrency; cancelling an order releases both back (via `Order::transitionTo`, alongside restock). Customers apply a coupon on the cart or checkout page and see the discount and new total live.

### See it: the demo shop

The ecommerce toggle is **off by default** - with it off, `/shop`, `/cart`, `/checkout`, and the admin "Shop" sidebar section are all correctly hidden/404 (that's the toggle working, not a bug). Turn it on at **Website Settings > Shop** (`/admin/settings`), or seed demo content which flips it on for you:

```bash
php artisan db:seed --class=DemoShopSeeder    # turns ecommerce ON + seeds sample products/categories
php artisan regions:import-indonesia          # one-time: pulls the Indonesia address dataset (needed for checkout's address picker)
```

Then visit:
- `/shop` - product grid (2 categories, a simple mug, a simple tote, a variable tee with 3 size variants, a draft sticker pack that only shows in the admin)
- `/shop/classic-tee` - variant selection + add to cart
- `/cart` and `/checkout` - full guest checkout flow
- `/admin/shop/products`, `/admin/shop/categories`, `/admin/shop/orders` (log in as `admin@seconds.test` / `password` first)

## Themes

A theme is a folder under `themes/<slug>/`:

```
themes/default/
  theme.json            # manifest: slug, name, version, supports, settings schema
  views/                # Blade templates
  assets/css/style.css  # the theme stylesheet (+ any js / images under assets/)
```

The active theme's views are registered under the `theme::` Blade namespace by `App\Support\ThemeManager`. The default theme is always registered as the base/fallback and the active theme is prepended so its templates override it. Exactly one theme is active at a time; activating one deactivates the rest in a transaction, and the active theme cannot be uninstalled. A theme's effective settings are resolved by `App\Support\ThemeSettings` - `theme.json` defaults merged with stored overrides.

### Theme assets (WordPress-style `style.css`)

A theme's CSS/JS/images live in its own `assets/` folder and are enqueued from Blade with the `@themeAsset('css/style.css')` directive - which resolves to `/themes/<active-slug>/assets/css/style.css?v=<mtime>`. That URL is served by `ThemeAssetController`, path-jailed with `realpath` to the theme's `assets/` folder and limited to a whitelist of static extensions (css/js/images/fonts), so a crafted path can't escape the theme. The `?v=<mtime>` cache-buster means edits (including live ones through the in-admin theme code editor) show up immediately. Only the tiny bit of genuinely dynamic CSS - the accent colour from the theme's `primary_color` setting - stays inline in the layout `<head>`, overriding the stylesheet's defaults; everything else is in `style.css`.

### Users & roles

`/admin/users` (`users.manage` permission - admin / super-admin only) is the user admin: list, create, edit, delete. Each user gets exactly one role (super-admin / developer / admin / editor). Passwords are set on create and optional on edit (blank keeps the current one). Guards prevent deleting your own account, and deleting **or** demoting the last remaining super-admin.

### Two kinds of settings

Seconds keeps design and site configuration separate:

- **Customize** (`/admin/themes/settings`) - per-theme **design tokens** from the active theme's `theme.json` (e.g. `primary_color`, `footer_text`). Resolved by `App\Support\ThemeSettings`. These follow the theme.
- **Website Settings** (`/admin/settings`) - site-level configuration in the `settings` table, resolved by `App\Support\SiteSettings`: site name/tagline/email, timezone (applied via `AppServiceProvider`), date format, posts-per-page, and the homepage choice (latest posts vs a static page). These survive theme switches.

### Theme code editor (optional, off by default)

An in-admin editor at `/admin/themes/code` for reading and writing theme template files (Blade/CSS/JS/JSON) - FTP-like, without leaving the admin. Because Blade compiles to PHP, editing a template is effectively running code on the server, so it is locked down:

- **Off by default.** Turn it on from the **Themes** admin page - a "Theme code editor" card with an Enable button and an "are you sure" confirmation. It stores the `theme_editor_enabled` site setting; no server/env change needed.
- Both the toggle and the editor are gated to the `themes.edit_code` permission (developer / super-admin only). A plain admin can't enable or use it.
- Every read/write is `realpath`-jailed to the `themes/` directory and limited to a whitelist of extensions (`config/seconds.php`).
- Saves back up the previous version to `storage/app/theme-backups` and are logged.
- The editor screen carries a prominent "for coders only" warning; a broken template runs on the live site.

Day to day the developer edits theme files directly in their own editor; this is a convenience for quick live tweaks.

## Content & front-end rendering

Pages and posts share one `contents` table, distinguished by a `type` discriminator (STI-lite). Query type-scoped via the `Page` / `Post` models, or cross-type via the `Content` base model. Content is `draft` / `published` / `scheduled`; the `published()` scope gates on status **and** publish time.

The front-end is a thin pipeline: `GET /` renders the theme's `home` template, and a single-segment catch-all `GET /{slug}` resolves a published content item and renders `theme::{type}` (`page` / `post`) with the content, its rendered blocks, and theme settings. Drafts, not-yet-published, and unknown slugs return 404.

Rich content is stored as **blocks** - an ordered `blocks` json array of `{ type, data }`. `App\Support\BlockRenderer` maps each block to a `theme::blocks.<type>` partial, falling back safely for unknown types so a bad block never fatals a page.

## Project layout

```
app/
  Enums/             # Role, Permission, ContentStatus, ProductType/Status, StockPolicy, OrderStatus
  Http/Controllers/  # FrontController (home + slug rendering + shop)
  Http/Middleware/   # EnsureStaff, EnsureEcommerceEnabled
  Livewire/          # Auth, Dashboard, Installer, Shop (catalog admin + storefront widget)
  Models/            # User, Setting, Theme, Content (+ Page, Post), Product(+Variant/Category), Order(+Item), Cart(+Item)
  Support/           # Feature, ThemeManager, ThemeManifest, ThemeSettings, BlockRenderer, Money
database/
  migrations/
  seeders/           # RolesAndPermissions, Settings, DatabaseSeeder
themes/
  default/           # ships pre-installed + active; base layout, page/post/home, block partials, shop templates
tests/
  Feature/           # Auth, Settings, Themes, Install, Content, Ecommerce
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

The admin **auto-generates the editor form** from that schema (`BlockRegistry` reads the active theme; the page/post editor renders an input per field type). Field types: `text`, `textarea`, `richtext`, `email`, `image`, `number`, `toggle`, `select`, and `repeater` (nested groups - e.g. a feature grid's cards). To add a block: declare it in `blocks.php` and ship a partial of the same name. The default theme ships Hero, Feature grid, Gallery, CTA, Image, Rich text, Testimonials, Form, Heading, Paragraph, and Divider as the reference set.

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

Phase 0 (Foundation), Phase 1 (Core CMS), Phase 1.5 (Block system v2 + Forms), the default theme build-out, the site-settings restructure + theme code editor, Phase 2 (Ecommerce core: catalog, storefront, cart, checkout/orders, inventory polish), **Phase 3 (Payments: manual bank transfer + Xendit, 3.0-3.4)**, and **Phase 4 (Delivery: manual + KiriminAja, 4.0-4.4) are all complete**, plus three post-Phase-4 additions: a real Indonesia address picker, free-shipping-over-a-minimum for manual delivery, and full API call logging for Payments + Delivery. Test suite: **456/456 green**.

What's shipped:
- Auth, RBAC (4 roles via spatie), admin shell, ecommerce toggle, first-run installer
- Content model (pages + posts, STI-lite), publish states, content blocks
- Admin CRUD: pages, posts, media library, menus, forms, theme install/activate/uninstall, theme settings
- **Schema-driven block system**: theme-defined blocks, auto-generated editor forms, repeater fields
- **Forms**: builder, submissions, `@form` directive + Form block, honeypot
- Front-end rendering: home, page, post, blog listing, category + tag archives
- SEO head (OG, canonical, noindex), /sitemap.xml, /robots.txt
- **Default theme**: full Option B design system + block library (Hero, Features, Gallery, CTA, Rich text, Testimonials, Image, Form, Heading, Paragraph, Divider)
- **Landing page template**: set any page to `template=landing` for full-width block rendering (no 720px article constraint) - ideal for homepages and marketing pages
- **Website Settings** (`/admin/settings`): site name, tagline, admin email, timezone (applied app-wide), date format, posts-per-page, and the WordPress-style Reading choice - homepage shows your latest posts or a static page
- **Front page**: pick a static page as your homepage from Website Settings, or the "Use as homepage" toggle on the page editor; the Pages list flags it with a "Homepage" badge. A default Home page is seeded on install.
- **Theme code editor** (`/admin/themes/code`): optional in-admin editing of theme files - see below
- **Ecommerce data model** (2.0): Product/ProductVariant/ProductCategory, Order/OrderItem, Cart/CartItem, `Money` integer-minor-units support (IDR), full order state machine (pending -> awaiting_payment -> paid -> fulfilled -> completed, + cancelled/refunded)
- **Catalog admin** (2.1): product + category CRUD (`/admin/shop/products`, `/admin/shop/categories`), simple + variable products with a variant editor, stock policies, category assignment - see "Catalog admin" above
- **Storefront catalog** (2.2): `/shop` grid with category filter, `/shop/{slug}` product detail page with a Livewire variant-selection widget - see "Storefront" above
- **Cart** (2.3): session/user-keyed db-backed cart, add/update/remove, stock-checked, live mini-cart + cart page - see "Cart" above
- **Checkout + orders** (2.4): guest-friendly checkout, order snapshotting, stock decrement, admin order list/detail with guarded status transitions + restock-on-cancel - see "Checkout + orders" above
- **Inventory + order-management polish** (2.5): manual stock adjustment, shared low-stock threshold across admin + storefront, stubbed order emails - see "Inventory + order-management polish" above
- **Payment gateway contract + state machine** (3.0): a `PaymentGateway` interface with a first-class `ManualGateway` (bank transfer), a `payments` table, and `PaymentService` - the single row-locked, monotonic path every "mark paid" flows through (admin confirmation, webhook, reconcile), so payments are idempotent and out-of-order-safe. A configurable payment window (default 120 min) stamps `payment_due_at`; the `payments:expire` command (scheduled every minute) cancels orders the customer hasn't acted on and returns their stock. The clock stops once a customer uploads proof (`submitted`) or pays.
- **Manual bank-transfer flow** (3.1): checkout now calls `PaymentService::initiate()` on every order, creating a pending payment and stamping the payment window. The order confirmation page shows the bank details, a live countdown to `payment_due_at`, and a proof-of-payment upload form (image/PDF, private disk, owner-gated). Admins configure bank details + the payment window at `/admin/shop/payments/settings`, and work a **verification queue** at `/admin/shop/payments` (`orders.manage`) to confirm (order -> paid) or reject (back to pending, with a reason) submitted proof.
- **Xendit activation + invoice creation** (3.2): `App\Payments\XenditGateway` calls Xendit's REST API directly via Laravel's `Http` facade (not the `xendit/xendit-php` SDK - no outbound network access to install it in the build environment; see `seconds-spec.md` §14 for the swap-back plan) to create a hosted **Xendit Invoice** covering VA/QRIS/e-wallet/card in one integration surface. Admins activate Xendit from `/admin/shop/payments/settings` (secret/public keys + webhook token, masked once saved, live-verified against Xendit's balance endpoint before activating; enabled-methods checkboxes) - this flips `payment_provider` to `xendit`, with manual staying one click away as a fallback. Checkout redirects the customer straight to the hosted invoice page when Xendit is active; the pending `Payment` row snapshots the invoice id, amount, currency, and full invoice response.
- **Xendit webhook handling + reconciliation** (3.3): `POST /webhooks/xendit` (the app's first CSRF-exempt route) verifies the `x-callback-token` header before any DB write, then applies the event through the same idempotent `PaymentService::applyEvent()` every other payment path uses - a `PAID` webhook marks the order paid, an `EXPIRED` one cancels + restocks it, unknown/bad-token requests are rejected safely. A `payments:reconcile` command (scheduled every 5 minutes) re-queries Xendit for any pending payment older than 5 minutes as a safety net for a missed webhook; the admin order detail screen has a matching "Re-check" button next to any pending Xendit payment.
- **Payments admin + methods UI** (3.4, closes Phase 3): the checkout payment-method selector now reflects the real active provider - bank transfer only in manual mode, or exactly the methods enabled in Xendit activation once it's live (toggling a method off there hides it at checkout immediately). `OrderDetail` gained a genuine **per-order payment timeline**: every payment attempt with its amount/created/paid timestamps, proof-upload details (reference + a link to view the file) and verifier for manual transfers, and a **Refund** action for any `paid` payment (`PaymentService::markRefunded()` - marks the payment refunded and moves the order to `refunded` if it can; the real gateway refund call is deferred/log-only in v1, matching the Forms-module stub convention).
- **Delivery provider contract + shipment state machine** (4.0, opens Phase 4): a `DeliveryProvider` interface with a first-class `ManualDeliveryProvider` (offline fulfilment - flat-rate quote, admin-entered courier + tracking, no webhook), a `shipments` table, and `ShipmentService` - the single row-locked, monotonic path every shipment advance flows through (tracking webhook, reconcile, admin manual advance), so fulfilment is idempotent and out-of-order-safe. `ShipmentStatus` (pending -> booked -> picked_up -> in_transit -> delivered, + cancelled/returned) reconciles with the order state machine (picked_up/in_transit -> `fulfilled`, delivered -> `completed`) without forking it. The real KiriminAja SDK (`kiriminaja/kiriminaja-php`) is installed and sits behind the interface.
- **Live rates at checkout** (4.1): `KiriminAjaProvider` wraps the real SDK behind the interface; `ShipmentService::driver(Kiriminaja)` is now live. Checkout's delivery-method selector shows real, selectable rate options (price + ETA) computed from the cart and entered address, and the chosen rate is snapshotted onto the order (`shipping_courier`/`shipping_service_code`/`shipping_service_name`/`shipping_total`) - locked at purchase like line items. If a live rate call fails or returns nothing, checkout gracefully falls back to a configurable flat rate rather than blocking.
- **Booking + pickup scheduling** (4.2): booking a shipment is a deliberate admin action (`Book shipment` on the order detail screen, available once the order is paid) rather than automatic at checkout - a real courier booking commits money. KiriminAja's `request_pickup` endpoint books and schedules in one call and returns the tracking number immediately, so there's no separate "schedule pickup" step for it in v1. Re-booking is guarded (no duplicates). Admins can also manually advance a shipment's status by hand (useful for the manual provider, which has no courier API to poll).
- **Tracking webhooks + fulfilment** (4.3): `POST /webhooks/kiriminaja` (CSRF-exempt, token-verified) advances shipment + order fulfilment state idempotently through the same locked path as everything else; a `delivery:reconcile` command (scheduled every 5 minutes) re-checks stale in-flight shipments as a safety net for a missed webhook, with a matching "Re-check" button on the order detail screen.
- **Delivery admin + settings** (4.4, closes Phase 4): `/admin/shop/delivery/settings` - origin address + parcel defaults + flat-rate fallback, then a Manual/KiriminAja provider grid (key activation verified against KiriminAja's balance endpoint before switching, plus an enabled-couriers filter). A **manual-shipment fallback** on the order detail screen lets an admin type a courier + tracking number by hand for any order, regardless of which provider is active site-wide.
- **Indonesia address picker** (post-Phase-4): a real Province → City/Regency → District cascading picker, backed by the official Kemendagri/BPS region dataset pulled one-time via `php artisan regions:import-indonesia` (38 provinces, 514 regencies, 7,285 districts, 83,762 villages). Replaces free-text city fields on checkout's destination address and delivery settings' origin address. A district's `kiriminaja_subdistrict_id` (nullable, backfilled separately once real KiriminAja credentials exist) is what actually unlocks live per-courier rates for that area - until then, checkout gracefully uses the flat rate.
- **Free shipping over a cart minimum** (post-Phase-4): manual/offline delivery now supports two pricing modes - a single flat rate (default), or free shipping once the cart subtotal reaches an admin-configured minimum (falling back to the flat rate below it). Set at `/admin/shop/delivery/settings`.
- **API call logging** (post-Phase-4): every outbound call to Xendit and KiriminAja, and every inbound webhook from them, is captured to an `api_logs` table (request, response, status, timing, success/failure) via `App\Support\ApiLogger` - built for debugging. View and filter them at `/admin/shop/api-logs`.

### Payments

Two modes behind one interface, chosen by a `payment_provider` setting: **manual bank transfer** (default) and **Xendit** (opt-in, VA/QRIS/e-wallet/card). Both share the payment-window expiry. Checkout, admin verification/activation, webhooks, reconciliation, the per-order timeline, and refund initiation are all live - see `seconds-spec.md` §18 for the full breakdown.

### Delivery

Two modes behind one interface, chosen by a `delivery_provider` setting: **manual/offline** (default, flat-rate-or-free-over-a-minimum + hand-entered tracking) and **KiriminAja** (opt-in, live rates/booking/tracking via the real SDK). Both share one fulfilment state machine that reconciles with order status, and a real Indonesia region picker for addresses. See `seconds-spec.md` §19 and §21 for the full breakdown.

Roadmap (see the spec for detail):

1. **Phase 1** - Core CMS - **DONE**
2. **Phase 1.5** - Block system v2 + Forms - **DONE**
3. **Phase 2** - Ecommerce core: catalog, cart, checkout, orders - **DONE** (2.0-2.5 all shipped).
4. **Phase 3** - Payments (manual bank transfer + Xendit) - **DONE** (3.0-3.4 all shipped).
5. **Phase 4** - Delivery (manual + KiriminAja) - **DONE** (4.0-4.4 all shipped).
6. **Phase 5** - Productization: gated theme code editor, more themes, hardening, docs, **storefront UI ownership refactor** (module-owned cart/checkout/portal/confirmation), the **customer accounts + portal** build-out, and the Phase-4 follow-up (a Province→City→District→Sub-district address picker to unlock real KiriminAja live rates).

## License

Proprietary - &Now. All rights reserved.
