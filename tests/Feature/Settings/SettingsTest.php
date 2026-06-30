<?php

use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use App\Support\Feature;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);
    seed(SettingsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Setting::flushCache();
});

it('seeds default settings', function () {
    expect(Setting::where('key', 'ecommerce')->value('value'))->toBe('false')
        ->and(Setting::where('key', 'site_name')->value('value'))->toBe('My Site');
});

it('can get a setting with a default', function () {
    expect(Setting::get('site_name'))->toBe('My Site')
        ->and(Setting::get('nonexistent', 'fallback'))->toBe('fallback');
});

it('can set and retrieve a setting', function () {
    Setting::set('site_name', 'Toko Baru');

    expect(Setting::get('site_name'))->toBe('Toko Baru');
});

it('ecommerce feature flag is false by default', function () {
    expect(Feature::ecommerce())->toBeFalse();
});

it('ecommerce feature flag returns true when set to true', function () {
    Setting::set('ecommerce', 'true');

    expect(Feature::ecommerce())->toBeTrue();
});

it('blocks ecommerce routes when toggle is off', function () {
    Setting::set('ecommerce', 'false');

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    actingAs($user)->get('/admin/shop/products')->assertNotFound();
    actingAs($user)->get('/admin/shop/orders')->assertNotFound();
});

it('allows ecommerce routes when toggle is on', function () {
    Setting::set('ecommerce', 'true');

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    actingAs($user)->get('/admin/shop/products')->assertOk();
    actingAs($user)->get('/admin/shop/orders')->assertOk();
});
