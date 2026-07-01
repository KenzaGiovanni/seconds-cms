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

it('manually adjusts stock for a simple product from the admin list', function () {
    $product = Product::create([
        'name' => 'Adjustable', 'slug' => 'adjustable', 'type' => 'simple',
        'status' => 'published', 'price' => 50000, 'stock_policy' => 'deny', 'stock' => 3,
    ]);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(ProductList::class)
        ->set('stockEdits.'.$product->id, 20)
        ->call('adjustStock', $product->id);

    expect($product->fresh()->stock)->toBe(20);
});

it('does not let stock go negative via manual adjustment', function () {
    $product = Product::create([
        'name' => 'Floor', 'slug' => 'floor', 'type' => 'simple',
        'status' => 'published', 'price' => 50000, 'stock_policy' => 'deny', 'stock' => 3,
    ]);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(ProductList::class)
        ->set('stockEdits.'.$product->id, -10)
        ->call('adjustStock', $product->id);

    expect($product->fresh()->stock)->toBe(0);
});

it('ignores a manual stock adjustment for a variable product', function () {
    $product = Product::create([
        'name' => 'Variable', 'slug' => 'variable-prod', 'type' => 'variable',
        'status' => 'published', 'stock_policy' => 'deny',
    ]);
    $product->variants()->create(['price' => 10000, 'stock' => 5]);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(ProductList::class)
        ->set('stockEdits.'.$product->id, 999)
        ->call('adjustStock', $product->id);

    expect($product->fresh()->stock)->toBeNull();
});

it('blocks an editor from adjusting stock', function () {
    $product = Product::create([
        'name' => 'Guarded', 'slug' => 'guarded', 'type' => 'simple',
        'status' => 'published', 'price' => 50000, 'stock_policy' => 'deny', 'stock' => 3,
    ]);

    $user = User::factory()->create();
    $user->assignRole(Role::Editor->value);

    test()->actingAs($user)->get('/admin/shop/products')->assertForbidden();
});

it('flags low stock in the admin product list', function () {
    $threshold = config('seconds.low_stock_threshold');

    $product = Product::create([
        'name' => 'Low Stock Item', 'slug' => 'low-stock-item', 'type' => 'simple',
        'status' => 'published', 'price' => 50000, 'stock_policy' => 'deny', 'stock' => $threshold,
    ]);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(ProductList::class)
        ->assertSee('low');
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
