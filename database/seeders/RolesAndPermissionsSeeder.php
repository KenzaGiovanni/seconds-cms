<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Create all permissions + roles and sync the role→permission matrix.
     * Idempotent: safe to re-run (install flow + tests call this).
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Permissions.
        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        // Roles + their permission sets (super-admin bypasses via Gate::before).
        foreach (RoleEnum::cases() as $roleEnum) {
            $role = Role::findOrCreate($roleEnum->value, 'web');
            $role->syncPermissions(
                array_map(fn (PermissionEnum $p) => $p->value, $roleEnum->permissions())
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
