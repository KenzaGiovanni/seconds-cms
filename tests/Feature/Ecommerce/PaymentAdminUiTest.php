<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Livewire\Shop\OrderDetail;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Theme;
use App\Models\User;
use App\Payments\PaymentService;
use App\Support\PaymentSettings;
use App\Support\ThemeManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

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

function adminUiOrder(): Order
{
    $product = Product::create([
        'name' => 'Kettle', 'slug' => 'kettle-'.Str::lower(Str::random(6)), 'type' => 'simple',
        'status' => 'published', 'price' => 50000, 'stock_policy' => 'deny', 'stock' => 10,
    ]);
    $product->decrementStock(1);

    $order = Order::create([
        'status' => OrderStatus::Pending,
        'email' => 'cust@example.com',
        'customer_name' => 'Cust',
        'currency' => 'IDR',
        'subtotal' => 50000,
        'total' => 50000,
        'placed_at' => now(),
    ]);
    $order->items()->create([
        'product_id' => $product->id, 'name' => $product->name,
        'unit_price' => 50000, 'quantity' => 1, 'line_total' => 50000, 'currency' => 'IDR',
    ]);
    $order->transitionTo(OrderStatus::AwaitingPayment);

    return $order->fresh('items');
}

it('checkout shows only bank transfer while manual is the active provider', function () {
    $this->get('/checkout')
        ->assertOk()
        ->assertSee('Bank transfer')
        ->assertDontSee('Virtual Account (VA)', false);
});

it('checkout reflects the enabled xendit methods once activated, hiding disabled ones', function () {
    PaymentSettings::setXenditKeys('sk_test', 'pk_test', 'wh_test');
    PaymentSettings::setXenditEnabledMethods([PaymentMethod::VirtualAccount]);
    PaymentSettings::setProvider(PaymentProvider::Xendit);

    $this->get('/checkout')
        ->assertOk()
        ->assertSee('Virtual Account')
        ->assertDontSee('QRIS');
});

it('marks a paid payment refunded and transitions the order to refunded', function () {
    $order = adminUiOrder();
    $payment = app(PaymentService::class)->initiate($order)->payment;
    app(PaymentService::class)->confirmManual($payment, User::factory()->create());

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(OrderDetail::class, ['id' => $order->id])
        ->call('refundPayment', $payment->id);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded)
        ->and($order->fresh()->status)->toBe(OrderStatus::Refunded);
});

it('does not refund a payment that has not been paid', function () {
    $order = adminUiOrder();
    $payment = app(PaymentService::class)->initiate($order)->payment;

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(OrderDetail::class, ['id' => $order->id])
        ->call('refundPayment', $payment->id);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($order->fresh()->status)->toBe(OrderStatus::AwaitingPayment);
});

it('admin order detail shows the proof-upload and verification timeline', function () {
    $order = adminUiOrder();
    $payment = app(PaymentService::class)->initiate($order)->payment;
    app(PaymentService::class)->submitProof($payment, 'proofs/receipt.jpg', 'BCA 12/07 08:14');

    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    app(PaymentService::class)->confirmManual($payment, $admin);

    Livewire::actingAs($admin)
        ->test(OrderDetail::class, ['id' => $order->id])
        ->assertSee('ref: BCA 12/07 08:14')
        ->assertSee('Verified by '.$admin->name);
});
