<?php

namespace App\Enums;

/**
 * Admin roles. All four are "staff" (can reach the admin area) in v1.
 * super-admin is the owner/installer and bypasses permission checks
 * entirely (Gate::before in AppServiceProvider).
 *
 * Key design rule (DESIGN.md / spec §6): only `developer` and `super-admin`
 * may edit raw theme code. Client admins/editors get settings + blocks only.
 */
enum Role: string
{
    case SuperAdmin = 'super-admin';
    case Developer = 'developer';
    case Admin = 'admin';
    case Editor = 'editor';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Developer => 'Developer',
            self::Admin => 'Admin',
            self::Editor => 'Editor',
        };
    }

    /**
     * Roles that may access the admin area. All roles are staff in v1
     * (client-facing storefront roles, if any, come later).
     *
     * @return list<self>
     */
    public static function staff(): array
    {
        return self::cases();
    }

    /**
     * Permissions granted to this role. super-admin is intentionally empty
     * here — it bypasses every check via Gate::before, so we don't enumerate.
     *
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => [],
            self::Developer => [
                Permission::ContentManage,
                Permission::ThemesManage,
                Permission::ThemesEditCode,
                Permission::SettingsManage,
            ],
            self::Admin => [
                Permission::ContentManage,
                Permission::ThemesManage,
                Permission::SettingsManage,
                Permission::UsersManage,
            ],
            self::Editor => [
                Permission::ContentManage,
            ],
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $r) => $r->value, self::cases());
    }
}
