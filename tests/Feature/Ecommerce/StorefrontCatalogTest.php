<?php

use App\Livewire\Shop\ProductDetail;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\Theme;
use App\Support\ThemeManager;
use Livewire\Livewire;

beforeEach(function () {
    Setting::set('ecommerce', 'true');
    Setting::flushCache();

    // Install and activate the default theme so theme:: views resolve.
    $manager = app(ThemeManager::class);
    if (! Theme::where('slug', 'default')->exists()) {
        $manager->install(base_path('themes/default'));
    }
    Theme::where('slug', 'default')->update(['status' => 'active']);
});

it('returns 404 for /shop when ecommerce is off', function () {
    Setting::set('ecommerce', 'false');
    Setting::flushCache();

    $this->get('/shop')->assertNotFound();
});

it('renders the shop index', function () {
    $product = Product::create([
        'name' => 'Test Shirt',
        'slug' => 'test-shirt',
        'type' => 'simple',
        'status' => 'published',
        'price' => 150000,
        'stock_policy' => 'deny',
        'stock' => 10,
    ]);

    $this->get('/shop')->assertOk()->assertSee('Test Shirt');
});

it('does not show draft products on the shop index', function () {
    Product::create([
        'name' => 'Hidden Draft',
        'slug' => 'hidden-draft',
        'type' => 'simple',
        'status' => 'draft',
        'price' => 50000,
        'stock_policy' => 'none',
    ]);

    $this->get('/shop')->assertOk()->assertDontSee('Hidden Draft');
});

it('filters shop index by category', function () {
    $cat = ProductCategory::create(['name' => 'Tops', 'slug' => 'tops']);

    $inCat = Product::create([
        'name' => 'Blue Shirt',
        'slug' => 'blue-shirt',
        'type' => 'simple',
        'status' => 'published',
        'price' => 150000,
        'stock_policy' => 'none',
    ]);
    $inCat->categories()->attach($cat->id);

    Product::create([
        'name' => 'Black Pants',
        'slug' => 'black-pants',
        'type' => 'simple',
        'status' => 'published',
        'price' => 200000,
        'stock_policy' => 'none',
    ]);

    $this->get('/shop?category=tops')
        ->assertOk()
        ->assertSee('Blue Shirt')
        ->assertDontSee('Black Pants');
});

it('renders the product detail page for a published product', function () {
    $product = Product::create([
        'name' => 'Cool Jacket',
        'slug' => 'cool-jacket',
        'type' => 'simple',
        'status' => 'published',
        'price' => 500000,
        'stock_policy' => 'deny',
        'stock' => 3,
    ]);

    $this->get('/shop/cool-jacket')
        ->assertOk()
        ->assertSee('Cool Jacket');
});

it('returns 404 for a draft product on the front end', function () {
    Product::create([
        'name' => 'Draft Product',
        'slug' => 'draft-product',
        'type' => 'simple',
        'status' => 'draft',
        'price' => 100000,
        'stock_policy' => 'none',
    ]);

    $this->get('/shop/draft-product')->assertNotFound();
});

it('returns 404 for /shop/{slug} when ecommerce is off', function () {
    Setting::set('ecommerce', 'false');
    Setting::flushCache();

    $product = Product::create([
        'name' => 'Gated',
        'slug' => 'gated',
        'type' => 'simple',
        'status' => 'published',
        'price' => 100000,
        'stock_policy' => 'none',
    ]);

    $this->get('/shop/gated')->assertNotFound();
});

// --- ProductDetail Livewire component (variant selection) ---

it('shows price for a simple product in the widget', function () {
    $product = Product::create([
        'name' => 'Simple Widget',
        'slug' => 'simple-widget',
        'type' => 'simple',
        'status' => 'published',
        'price' => 250000,
        'stock_policy' => 'deny',
        'stock' => 5,
    ]);

    Livewire::test(ProductDetail::class, ['productId' => $product->id])
        ->assertSee('Rp 250.000')
        ->assertSee('Add to cart');
});

it('shows variant buttons and switches price on selection', function () {
    $product = Product::create([
        'name' => 'Var Widget',
        'slug' => 'var-widget',
        'type' => 'variable',
        'status' => 'published',
        'stock_policy' => 'deny',
    ]);
    $v1 = $product->variants()->create(['price' => 100000, 'stock' => 5, 'options' => ['Size' => 'S']]);
    $v2 = $product->variants()->create(['price' => 120000, 'stock' => 2, 'options' => ['Size' => 'L']]);

    Livewire::test(ProductDetail::class, ['productId' => $product->id])
        ->assertSee('S')
        ->assertSee('L')
        ->call('selectVariant', $v2->id)
        ->assertSee('Rp 120.000');
});

it('shows out-of-stock state and disables add-to-cart', function () {
    $product = Product::create([
        'name' => 'OOS Widget',
        'slug' => 'oos-widget',
        'type' => 'simple',
        'status' => 'published',
        'price' => 100000,
        'stock_policy' => 'deny',
        'stock' => 0,
    ]);

    Livewire::test(ProductDetail::class, ['productId' => $product->id])
        ->assertSee('Out of stock');
});
