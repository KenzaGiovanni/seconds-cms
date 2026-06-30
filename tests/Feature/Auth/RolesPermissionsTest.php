<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('seeds all roles and permissions', function () {
    expect(Spatie\Permission\Models\Role::count())->toBe(count(Role::cases()))
        ->and(Spatie\Permission\Models\Permission::count())->toBe(count(Permission::cases()));
});

it('blocks users without a staff role from the admin area', function () {
    $user = User::factory()->create(); // no role

    actingAs($user)->get('/admin')->assertForbidden();
});

it('lets each staff role into the admin area', function (string $role) {
    $user = User::factory()->create();
    $user->assignRole($role);

    actingAs($user)->get('/admin')->assertOk();
})->with(Role::values());

it('grants super-admin every ability via Gate::before', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::SuperAdmin->value);

    expect($user->can(Permission::ThemesEditCode->value))->toBeTrue()
        ->and($user->can(Permission::UsersManage->value))->toBeTrue()
        ->and($user->can('anything.not.even.defined'))->toBeTrue();
});

it('restricts raw theme-code editing to developer and super-admin', function () {
    $developer = User::factory()->create();
    $developer->assignRole(Role::Developer->value);

    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    expect($developer->can(Permission::ThemesEditCode->value))->toBeTrue()
        ->and($admin->can(Permission::ThemesEditCode->value))->toBeFalse()
        ->and($editor->can(Permission::ThemesEditCode->value))->toBeFalse();
});

it('gives admin user management but not editor', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    expect($admin->can(Permission::UsersManage->value))->toBeTrue()
        ->and($editor->can(Permission::UsersManage->value))->toBeFalse();
});
