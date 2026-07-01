<?php

use App\Enums\Role;
use App\Livewire\Shop\ProductList;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Theme;
use App\Models\User;
use App\Support\ThemeManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\seed;

beforeEach(function () {
    Setting::set('ecommerce', 'true');
    Setting::flushCache();

    seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('blocks an editor from the product list', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Editor->value);

    test()->actingAs($user)->get('/admin/shop/products')->assertForbidden();
});

it('shows stock read-only in the admin product list (no inline editor)', function () {
    $product = Product::create([
        'name' => 'Stocked Item', 'slug' => 'stocked-item', 'type' => 'simple',
        'status' => 'published', 'price' => 50000, 'stock_policy' => 'deny', 'stock' => 42,
    ]);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(ProductList::class)
        ->assertSee('42')
        ->assertDontSee('wire:model="stockEdits', false)
        ->assertDontSee('adjustStock');
});

it('flags low stock in the admin product list', function () {
    $threshold = config('seconds.low_stock_threshold');

    Product::create([
        'name' => 'Low Stock Item', 'slug' => 'low-stock-item', 'type' => 'simple',
        'status' => 'published', 'price' => 50000, 'stock_policy' => 'deny', 'stock' => $threshold,
    ]);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(ProductList::class)
        ->assertSee('low');
});

it('flags out-of-stock in the admin product list', function () {
    Product::create([
        'name' => 'Sold Out Item', 'slug' => 'sold-out-item', 'type' => 'simple',
        'status' => 'published', 'price' => 50000, 'stock_policy' => 'deny', 'stock' => 0,
    ]);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(ProductList::class)
        ->assertSee('out of stock');
});

it('flags low stock on the storefront shop index', function () {
    $manager = app(ThemeManager::class);
    if (! Theme::where('slug', 'default')->exists()) {
        $manager->install(base_path('themes/default'));
    }
    Theme::where('slug', 'default')->update(['status' => 'active']);

    $threshold = config('seconds.low_stock_threshold');

    Product::create([
        'name' => 'Almost Gone', 'slug' => 'almost-gone', 'type' => 'simple',
        'status' => 'published', 'price' => 50000, 'stock_policy' => 'deny', 'stock' => $threshold,
    ]);

    $this->get('/shop')->assertOk()->assertSee('Only '.$threshold.' left');
});
