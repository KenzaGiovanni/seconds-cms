<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Livewire\Shop\ProductCategoryForm;
use App\Livewire\Shop\ProductCategoryList;
use App\Livewire\Shop\ProductForm;
use App\Livewire\Shop\ProductList;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    // Enable ecommerce for all tests in this file.
    Setting::set('ecommerce', 'true');
});

// --- Access control ---

it('blocks an editor from the product list', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Editor->value);

    actingAs($user)->get('/admin/shop/products')->assertForbidden();
});

it('lets an admin reach the product list', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    actingAs($user)
        ->get('/admin/shop/products')
        ->assertOk();
});

it('blocks access when ecommerce is off', function () {
    Setting::set('ecommerce', 'false');
    Setting::flushCache();

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    actingAs($user)->get('/admin/shop/products')->assertNotFound();
});

// --- Product categories CRUD ---

it('creates a product category', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(ProductCategoryForm::class)
        ->set('name', 'T-Shirts')
        ->set('slug', 't-shirts')
        ->call('save');

    expect(ProductCategory::where('slug', 't-shirts')->exists())->toBeTrue();
});

it('auto-generates a slug from the name', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(ProductCategoryForm::class)
        ->set('name', 'Summer Sale')
        ->assertSet('slug', 'summer-sale');
});

it('assigns a parent category', function () {
    $parent = ProductCategory::create(['name' => 'Clothing', 'slug' => 'clothing']);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(ProductCategoryForm::class)
        ->set('name', 'T-Shirts')
        ->set('slug', 't-shirts')
        ->set('parentId', $parent->id)
        ->call('save');

    expect(ProductCategory::where('slug', 't-shirts')->first()->parent_id)->toBe($parent->id);
});

it('deletes a category', function () {
    $cat = ProductCategory::create(['name' => 'Old', 'slug' => 'old']);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(ProductCategoryList::class)
        ->call('delete', $cat->id);

    expect(ProductCategory::find($cat->id))->toBeNull();
});

// --- Simple product CRUD ---

it('creates a simple product', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(ProductForm::class)
        ->set('name', 'Blue Shirt')
        ->set('type', 'simple')
        ->set('status', 'published')
        ->set('price', '150000')
        ->set('sku', 'SHIRT-BLUE')
        ->set('stock', '10')
        ->set('stockPolicy', 'deny')
        ->call('save');

    $product = Product::where('slug', 'blue-shirt')->first();
    expect($product)->not->toBeNull()
        ->and($product->price)->toBe(150000)
        ->and($product->sku)->toBe('SHIRT-BLUE')
        ->and($product->stock)->toBe(10);
});

it('updates a simple product', function () {
    $product = Product::create([
        'name' => 'Old Name',
        'slug' => 'old-name',
        'type' => 'simple',
        'status' => 'draft',
        'price' => 100000,
        'stock_policy' => 'deny',
    ]);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(ProductForm::class, ['id' => $product->id])
        ->set('name', 'New Name')
        ->set('price', '200000')
        ->call('save');

    expect($product->fresh()->name)->toBe('New Name')
        ->and($product->fresh()->price)->toBe(200000);
});

it('assigns categories to a product', function () {
    $cat = ProductCategory::create(['name' => 'Tops', 'slug' => 'tops']);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(ProductForm::class)
        ->set('name', 'Test Shirt')
        ->set('type', 'simple')
        ->set('status', 'draft')
        ->set('price', '100000')
        ->set('stockPolicy', 'deny')
        ->set('selectedCategories', [(string) $cat->id])
        ->call('save');

    $product = Product::where('slug', 'test-shirt')->first();
    expect($product->categories->pluck('id')->contains($cat->id))->toBeTrue();
});

it('deletes a product', function () {
    $product = Product::create([
        'name' => 'Delete Me',
        'slug' => 'delete-me',
        'type' => 'simple',
        'status' => 'draft',
        'price' => 50000,
        'stock_policy' => 'none',
    ]);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(ProductList::class)
        ->call('delete', $product->id);

    expect(Product::find($product->id))->toBeNull();
});

// --- Variable product + variants ---

it('creates a variable product with variants', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(ProductForm::class)
        ->set('name', 'Multi Shirt')
        ->set('type', 'variable')
        ->set('status', 'published')
        ->set('stockPolicy', 'deny')
        ->call('addVariant')
        ->set('variants.0.price', '100000')
        ->set('variants.0.sku', 'MS-S')
        ->set('variants.0.stock', '5')
        ->set('variants.0.opt1k', 'Size')
        ->set('variants.0.opt1v', 'S')
        ->call('addVariant')
        ->set('variants.1.price', '100000')
        ->set('variants.1.sku', 'MS-L')
        ->set('variants.1.stock', '3')
        ->set('variants.1.opt1k', 'Size')
        ->set('variants.1.opt1v', 'L')
        ->call('save');

    $product = Product::where('slug', 'multi-shirt')->first();
    expect($product)->not->toBeNull()
        ->and($product->variants->count())->toBe(2)
        ->and($product->variants->first()->price)->toBe(100000)
        ->and($product->variants->first()->options)->toBe(['Size' => 'S']);
});

it('removes a variant when editing a variable product', function () {
    $product = Product::create([
        'name' => 'Var Shirt',
        'slug' => 'var-shirt',
        'type' => 'variable',
        'status' => 'draft',
        'stock_policy' => 'deny',
    ]);
    $v1 = $product->variants()->create(['price' => 50000, 'sku' => 'VS-S', 'stock' => 5, 'options' => ['Size' => 'S']]);
    $v2 = $product->variants()->create(['price' => 50000, 'sku' => 'VS-L', 'stock' => 3, 'options' => ['Size' => 'L']]);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(ProductForm::class, ['id' => $product->id])
        ->call('removeVariant', 0) // remove first variant
        ->call('save');

    expect($product->variants()->count())->toBe(1);
});

// --- Permission check ---

it('returns 403 when editor visits the products page', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Editor->value);

    actingAs($user)->get('/admin/shop/products')->assertForbidden();
});
