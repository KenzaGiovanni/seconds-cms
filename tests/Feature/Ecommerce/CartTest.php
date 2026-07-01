<?php

use App\Livewire\Shop\CartItems;
use App\Livewire\Shop\ProductDetail;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Theme;
use App\Models\User;
use App\Support\CartManager;
use App\Support\ThemeManager;
use Livewire\Livewire;

beforeEach(function () {
    Setting::set('ecommerce', 'true');
    Setting::flushCache();

    $manager = app(ThemeManager::class);
    if (! Theme::where('slug', 'default')->exists()) {
        $manager->install(base_path('themes/default'));
    }
    Theme::where('slug', 'default')->update(['status' => 'active']);
});

it('returns 404 for /cart when ecommerce is off', function () {
    Setting::set('ecommerce', 'false');
    Setting::flushCache();

    $this->get('/cart')->assertNotFound();
});

it('renders the cart page', function () {
    $this->get('/cart')->assertOk()->assertSee('Your Cart');
});

// --- CartManager service ---

it('adds a simple product to the cart', function () {
    $product = Product::create([
        'name' => 'Widget', 'slug' => 'widget', 'type' => 'simple',
        'status' => 'published', 'price' => 100000, 'stock_policy' => 'deny', 'stock' => 10,
    ]);

    $cart = app(CartManager::class);
    $item = $cart->addItem($product, 2);

    expect($item->quantity)->toBe(2)
        ->and($cart->current()->items()->count())->toBe(1);
});

it('merges quantity when adding the same product twice', function () {
    $product = Product::create([
        'name' => 'Widget', 'slug' => 'widget', 'type' => 'simple',
        'status' => 'published', 'price' => 100000, 'stock_policy' => 'deny', 'stock' => 10,
    ]);

    $cart = app(CartManager::class);
    $cart->addItem($product, 2);
    $cart->addItem($product, 3);

    expect($cart->current()->items()->count())->toBe(1)
        ->and($cart->current()->items()->first()->quantity)->toBe(5);
});

it('adds distinct variants of the same product as separate lines', function () {
    $product = Product::create([
        'name' => 'Shirt', 'slug' => 'shirt', 'type' => 'variable',
        'status' => 'published', 'stock_policy' => 'deny',
    ]);
    $small = $product->variants()->create(['price' => 100000, 'stock' => 5, 'options' => ['Size' => 'S']]);
    $large = $product->variants()->create(['price' => 100000, 'stock' => 5, 'options' => ['Size' => 'L']]);

    $cart = app(CartManager::class);
    $cart->addItem($product, 1, $small);
    $cart->addItem($product, 1, $large);

    expect($cart->current()->items()->count())->toBe(2);
});

it('enforces stock limit when adding to cart', function () {
    $product = Product::create([
        'name' => 'Limited', 'slug' => 'limited', 'type' => 'simple',
        'status' => 'published', 'price' => 100000, 'stock_policy' => 'deny', 'stock' => 2,
    ]);

    $cart = app(CartManager::class);

    expect(fn () => $cart->addItem($product, 3))->toThrow(RuntimeException::class);
});

it('updates line item quantity', function () {
    $product = Product::create([
        'name' => 'Widget', 'slug' => 'widget', 'type' => 'simple',
        'status' => 'published', 'price' => 100000, 'stock_policy' => 'deny', 'stock' => 10,
    ]);

    $cart = app(CartManager::class);
    $item = $cart->addItem($product, 2);
    $cart->updateQuantity($item, 5);

    expect($item->fresh()->quantity)->toBe(5);
});

it('removes the item when quantity is updated to zero', function () {
    $product = Product::create([
        'name' => 'Widget', 'slug' => 'widget', 'type' => 'simple',
        'status' => 'published', 'price' => 100000, 'stock_policy' => 'deny', 'stock' => 10,
    ]);

    $cart = app(CartManager::class);
    $item = $cart->addItem($product, 2);
    $cart->updateQuantity($item, 0);

    expect(Cart::find($cart->current()->id)->items()->count())->toBe(0);
});

it('removes an item directly', function () {
    $product = Product::create([
        'name' => 'Widget', 'slug' => 'widget', 'type' => 'simple',
        'status' => 'published', 'price' => 100000, 'stock_policy' => 'deny', 'stock' => 10,
    ]);

    $cart = app(CartManager::class);
    $item = $cart->addItem($product, 2);
    $cart->removeItem($item);

    expect($cart->current()->items()->count())->toBe(0);
});

it('computes correct totals across multiple lines', function () {
    $a = Product::create([
        'name' => 'A', 'slug' => 'a', 'type' => 'simple',
        'status' => 'published', 'price' => 100000, 'stock_policy' => 'none',
    ]);
    $b = Product::create([
        'name' => 'B', 'slug' => 'b', 'type' => 'simple',
        'status' => 'published', 'price' => 50000, 'stock_policy' => 'none',
    ]);

    $cart = app(CartManager::class);
    $cart->addItem($a, 2); // 200000
    $cart->addItem($b, 3); // 150000

    $totals = $cart->totals();

    expect($totals['subtotal'])->toBe(350000)
        ->and($totals['itemCount'])->toBe(5);
});

it('persists the cart across requests for a guest via session', function () {
    $product = Product::create([
        'name' => 'Widget', 'slug' => 'widget', 'type' => 'simple',
        'status' => 'published', 'price' => 100000, 'stock_policy' => 'none',
    ]);

    $this->withSession(['_test' => true]);
    $sessionId = session()->getId();

    app(CartManager::class)->addItem($product, 1);

    $cart = Cart::where('session_id', $sessionId)->first();
    expect($cart)->not->toBeNull()
        ->and($cart->items()->count())->toBe(1);
});

it('scopes the cart to the authenticated user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $product = Product::create([
        'name' => 'Widget', 'slug' => 'widget', 'type' => 'simple',
        'status' => 'published', 'price' => 100000, 'stock_policy' => 'none',
    ]);

    app(CartManager::class)->addItem($product, 1);

    $cart = Cart::where('user_id', $user->id)->first();
    expect($cart)->not->toBeNull()
        ->and($cart->items()->count())->toBe(1);
});

// --- Livewire components ---

it('adds to cart from the product detail widget', function () {
    $product = Product::create([
        'name' => 'Cool Hat', 'slug' => 'cool-hat', 'type' => 'simple',
        'status' => 'published', 'price' => 75000, 'stock_policy' => 'deny', 'stock' => 5,
    ]);

    Livewire::test(ProductDetail::class, ['productId' => $product->id])
        ->set('quantity', 2)
        ->call('addToCart')
        ->assertSee('Added to cart');

    expect(app(CartManager::class)->current()->items()->first()->quantity)->toBe(2);
});

it('shows an error when adding more than available stock from the widget', function () {
    $product = Product::create([
        'name' => 'Scarce', 'slug' => 'scarce', 'type' => 'simple',
        'status' => 'published', 'price' => 75000, 'stock_policy' => 'deny', 'stock' => 1,
    ]);

    Livewire::test(ProductDetail::class, ['productId' => $product->id])
        ->set('quantity', 5)
        ->call('addToCart')
        ->assertSee('Not enough stock available.');
});

it('lists cart items and removes one via the cart-items widget', function () {
    $product = Product::create([
        'name' => 'Removable', 'slug' => 'removable', 'type' => 'simple',
        'status' => 'published', 'price' => 60000, 'stock_policy' => 'none',
    ]);

    $item = app(CartManager::class)->addItem($product, 1);

    Livewire::test(CartItems::class)
        ->assertSee('Removable')
        ->call('removeItem', $item->id)
        ->assertDontSee('Removable');
});

it('updates quantity via the cart-items widget', function () {
    $product = Product::create([
        'name' => 'Adjustable', 'slug' => 'adjustable', 'type' => 'simple',
        'status' => 'published', 'price' => 60000, 'stock_policy' => 'deny', 'stock' => 10,
    ]);

    $item = app(CartManager::class)->addItem($product, 1);

    Livewire::test(CartItems::class)
        ->call('updateQuantity', $item->id, 4);

    expect($item->fresh()->quantity)->toBe(4);
});
