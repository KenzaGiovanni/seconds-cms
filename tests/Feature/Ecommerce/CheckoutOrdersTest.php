<?php

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Livewire\Shop\Checkout;
use App\Livewire\Shop\OrderDetail;
use App\Livewire\Shop\OrderList;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Theme;
use App\Models\User;
use App\Support\CartManager;
use App\Support\ThemeManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    Setting::set('ecommerce', 'true');
    Setting::flushCache();

    $manager = app(ThemeManager::class);
    if (! Theme::where('slug', 'default')->exists()) {
        $manager->install(base_path('themes/default'));
    }
    Theme::where('slug', 'default')->update(['status' => 'active']);

    seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function checkoutFormData(): array
{
    return array_merge([
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'phone' => '08123456789',
        'addressLine' => 'Jl. Sudirman No. 1',
        'postalCode' => '10220',
    ], seedTestRegion());
}

it('returns 404 for /checkout when ecommerce is off', function () {
    Setting::set('ecommerce', 'false');
    Setting::flushCache();

    $this->get('/checkout')->assertNotFound();
});

it('renders the checkout page', function () {
    $this->get('/checkout')->assertOk()->assertSee('Checkout');
});

it('places an order from the cart as a guest', function () {
    $product = Product::create([
        'name' => 'Notebook', 'slug' => 'notebook', 'type' => 'simple',
        'status' => 'published', 'price' => 45000, 'stock_policy' => 'deny', 'stock' => 10,
    ]);

    app(CartManager::class)->addItem($product, 2);

    $data = checkoutFormData();

    $component = Livewire::test(Checkout::class);
    foreach ($data as $key => $value) {
        $component->set($key, $value);
    }
    $component->call('placeOrder');

    $order = Order::where('email', 'budi@example.com')->first();

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(OrderStatus::AwaitingPayment)
        ->and($order->user_id)->toBeNull()
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->quantity)->toBe(2)
        ->and($order->items->first()->unit_price)->toBe(45000)
        ->and($order->subtotal)->toBe(90000)
        ->and($order->total)->toBe(90000);
});

it('snapshots the chosen delivery rate onto the order total (Phase 4.1)', function () {
    Setting::set('delivery_flat_rate', 12000);
    Setting::flushCache();

    $product = Product::create([
        'name' => 'Mug', 'slug' => 'mug', 'type' => 'simple',
        'status' => 'published', 'price' => 45000, 'stock_policy' => 'deny', 'stock' => 10,
    ]);
    app(CartManager::class)->addItem($product, 1);

    $component = Livewire::test(Checkout::class);
    foreach (checkoutFormData() as $key => $value) {
        $component->set($key, $value);
    }
    $component->call('placeOrder');

    $order = Order::where('email', 'budi@example.com')->first();

    expect($order->shipping_total)->toBe(12000);
    expect($order->shipping_courier)->toBe('manual');
    expect($order->shipping_service_code)->toBe('flat');
    expect($order->total)->toBe(45000 + 12000);
});

it('snapshots product name/price on the order item even if the product changes later', function () {
    $product = Product::create([
        'name' => 'Original Name', 'slug' => 'orig', 'type' => 'simple',
        'status' => 'published', 'price' => 100000, 'stock_policy' => 'none',
    ]);

    app(CartManager::class)->addItem($product, 1);

    $data = checkoutFormData();
    $component = Livewire::test(Checkout::class);
    foreach ($data as $key => $value) {
        $component->set($key, $value);
    }
    $component->call('placeOrder');

    $product->update(['name' => 'Changed Name', 'price' => 999999]);

    $order = Order::where('email', 'budi@example.com')->first();
    expect($order->items->first()->name)->toBe('Original Name')
        ->and($order->items->first()->unit_price)->toBe(100000);
});

it('decrements stock when an order is placed', function () {
    $product = Product::create([
        'name' => 'Mug', 'slug' => 'mug', 'type' => 'simple',
        'status' => 'published', 'price' => 30000, 'stock_policy' => 'deny', 'stock' => 5,
    ]);

    app(CartManager::class)->addItem($product, 3);

    $data = checkoutFormData();
    $component = Livewire::test(Checkout::class);
    foreach ($data as $key => $value) {
        $component->set($key, $value);
    }
    $component->call('placeOrder');

    expect($product->fresh()->stock)->toBe(2);
});

it('clears the cart after placing an order', function () {
    $product = Product::create([
        'name' => 'Mug', 'slug' => 'mug', 'type' => 'simple',
        'status' => 'published', 'price' => 30000, 'stock_policy' => 'none',
    ]);

    app(CartManager::class)->addItem($product, 1);

    $data = checkoutFormData();
    $component = Livewire::test(Checkout::class);
    foreach ($data as $key => $value) {
        $component->set($key, $value);
    }
    $component->call('placeOrder');

    expect(app(CartManager::class)->current()->items()->count())->toBe(0);
});

it('rejects checkout when the cart is empty', function () {
    $data = checkoutFormData();
    $component = Livewire::test(Checkout::class);
    foreach ($data as $key => $value) {
        $component->set($key, $value);
    }
    $component->call('placeOrder')->assertSee('Your cart is empty.');

    expect(Order::count())->toBe(0);
});

it('validates required checkout fields', function () {
    Livewire::test(Checkout::class)
        ->call('placeOrder')
        ->assertHasErrors(['name', 'email', 'addressLine', 'provinceCode', 'regencyCode', 'districtCode', 'postalCode']);
});

// --- Order confirmation ownership ---

it('shows the confirmation page to the guest who just placed the order', function () {
    $product = Product::create([
        'name' => 'Widget', 'slug' => 'widget', 'type' => 'simple',
        'status' => 'published', 'price' => 30000, 'stock_policy' => 'none',
    ]);
    app(CartManager::class)->addItem($product, 1);

    $data = checkoutFormData();
    $component = Livewire::test(Checkout::class);
    foreach ($data as $key => $value) {
        $component->set($key, $value);
    }
    $component->call('placeOrder');

    $order = Order::first();

    $this->get('/order/'.$order->number)->assertOk()->assertSee($order->number);
});

it('blocks viewing someone elses order confirmation without ownership', function () {
    $order = Order::create([
        'status' => 'awaiting_payment',
        'email' => 'someone@example.com',
        'customer_name' => 'Someone Else',
        'shipping_address' => ['address_line' => 'X', 'city' => 'Y', 'postal_code' => 'Z'],
    ]);

    // Fresh test request, no session flag pointing at this order.
    $this->get('/order/'.$order->number)->assertNotFound();
});

// --- Admin order management ---

it('lists orders in the admin', function () {
    $order = Order::create([
        'status' => 'awaiting_payment',
        'email' => 'a@example.com',
        'customer_name' => 'A Customer',
        'shipping_address' => ['address_line' => 'X'],
    ]);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(OrderList::class)
        ->assertSee($order->number);
});

it('blocks an editor from admin order list', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Editor->value);

    actingAs($user)->get('/admin/shop/orders')->assertForbidden();
});

it('transitions an order status from the admin detail screen', function () {
    $order = Order::create([
        'status' => 'awaiting_payment',
        'email' => 'a@example.com',
        'customer_name' => 'A Customer',
        'shipping_address' => ['address_line' => 'X'],
    ]);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(OrderDetail::class, ['id' => $order->id])
        ->call('transitionTo', 'paid');

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->fresh()->paid_at)->not->toBeNull();
});

it('restocks products when an awaiting-payment order is cancelled', function () {
    $product = Product::create([
        'name' => 'Restockable', 'slug' => 'restockable', 'type' => 'simple',
        'status' => 'published', 'price' => 50000, 'stock_policy' => 'deny', 'stock' => 3,
    ]);

    $order = Order::create([
        'status' => 'awaiting_payment',
        'email' => 'a@example.com',
        'customer_name' => 'A Customer',
        'shipping_address' => ['address_line' => 'X'],
    ]);
    $order->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'unit_price' => 50000,
        'quantity' => 2,
        'line_total' => 100000,
        'currency' => 'IDR',
    ]);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(OrderDetail::class, ['id' => $order->id])
        ->call('transitionTo', 'cancelled');

    expect($product->fresh()->stock)->toBe(5); // 3 + 2 restocked
});

it('does not restock when cancelling a pending order (never left pending stock reserved)', function () {
    $product = Product::create([
        'name' => 'NoRestock', 'slug' => 'no-restock', 'type' => 'simple',
        'status' => 'published', 'price' => 50000, 'stock_policy' => 'deny', 'stock' => 3,
    ]);

    $order = Order::create([
        'status' => 'pending',
        'email' => 'a@example.com',
        'customer_name' => 'A Customer',
        'shipping_address' => ['address_line' => 'X'],
    ]);
    $order->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'unit_price' => 50000,
        'quantity' => 2,
        'line_total' => 100000,
        'currency' => 'IDR',
    ]);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(OrderDetail::class, ['id' => $order->id])
        ->call('transitionTo', 'cancelled');

    expect($product->fresh()->stock)->toBe(3);
});

it('rejects an illegal transition', function () {
    $order = Order::create([
        'status' => 'completed',
        'email' => 'a@example.com',
        'customer_name' => 'A Customer',
        'shipping_address' => ['address_line' => 'X'],
    ]);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(OrderDetail::class, ['id' => $order->id])
        ->call('transitionTo', 'pending');

    expect($order->fresh()->status)->toBe(OrderStatus::Completed);
});
