<?php

namespace App\Enums;

/**
 * Capabilities checked across the admin. Stored as spatie permissions
 * (guard: web). super-admin bypasses all of these via Gate::before.
 *
 * This list grows per phase — keep it the single source of capability names.
 */
enum Permission: string
{
    // Content (pages, posts, media, menus) — Phase 1.
    case ContentManage = 'content.manage';

    // Themes: install / activate / configure settings — Phase 1.
    case ThemesManage = 'themes.manage';

    // Themes: edit raw Blade template code — Phase 5 editor, gated NOW.
    // Per DESIGN/spec: developer + super-admin only.
    case ThemesEditCode = 'themes.edit_code';

    // Global settings + the ecommerce toggle — Phase 0.4.
    case SettingsManage = 'settings.manage';

    // Manage admin users + their roles.
    case UsersManage = 'users.manage';

    // Ecommerce: products + categories — Phase 2.1.
    case ProductsManage = 'products.manage';

    // Ecommerce: orders + fulfilment — Phase 2.4.
    case OrdersManage = 'orders.manage';

    public function label(): string
    {
        return match ($this) {
            self::ContentManage => 'Manage content',
            self::ThemesManage => 'Manage themes',
            self::ThemesEditCode => 'Edit theme code',
            self::SettingsManage => 'Manage settings',
            self::UsersManage => 'Manage users',
            self::ProductsManage => 'Manage products',
            self::OrdersManage => 'Manage orders',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $p) => $p->value, self::cases());
    }
}
