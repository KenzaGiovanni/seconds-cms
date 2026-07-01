<?php

use App\Console\Commands\ReconcileXenditPayments;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Livewire\Shop\OrderDetail;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\PaymentSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\seed;

beforeEach(function () {
    Setting::set('ecommerce', 'true');
    Setting::flushCache();
    PaymentSettings::setXenditKeys('sk_test', 'pk_test', 'wh_secret_token');

    seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

/** Build an order sitting in awaiting_payment with a pending Xendit payment. */
function xenditOrder(int $stock = 10, int $qty = 2): array
{
    $product = Product::create([
        'name' => 'Kettle', 'slug' => 'kettle-'.Str::lower(Str::random(6)), 'type' => 'simple',
        'status' => 'published', 'price' => 50000, 'stock_policy' => 'deny', 'stock' => $stock, 'sku' => 'K1',
    ]);

    $product->decrementStock($qty);

    $order = Order::create([
        'status' => OrderStatus::Pending,
        'email' => 'cust@example.com',
        'customer_name' => 'Cust',
        'currency' => 'IDR',
        'subtotal' => 50000 * $qty,
        'total' => 50000 * $qty,
        'placed_at' => now(),
    ]);

    $order->items()->create([
        'product_id' => $product->id, 'name' => $product->name, 'sku' => 'K1',
        'unit_price' => 50000, 'quantity' => $qty, 'line_total' => 50000 * $qty, 'currency' => 'IDR',
    ]);

    $order->transitionTo(OrderStatus::AwaitingPayment);

    $payment = Payment::create([
        'order_id' => $order->id, 'gateway' => PaymentProvider::Xendit, 'method' => PaymentMethod::VirtualAccount,
        'external_id' => 'inv_wh_'.Str::random(8), 'status' => PaymentStatus::Pending,
        'amount' => 50000 * $qty, 'currency' => 'IDR',
    ]);

    return [$order->fresh('items'), $payment];
}

it('marks an order paid via a valid xendit webhook', function () {
    [$order, $payment] = xenditOrder();

    $this->postJson('/webhooks/xendit', [
        'id' => $payment->external_id,
        'external_id' => 'ext-ref',
        'status' => 'PAID',
    ], ['x-callback-token' => 'wh_secret_token'])->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Paid);
});

it('rejects a webhook with a bad or missing token, applying no state change', function () {
    [$order, $payment] = xenditOrder();

    $this->postJson('/webhooks/xendit', [
        'id' => $payment->external_id,
        'status' => 'PAID',
    ], ['x-callback-token' => 'wrong-token'])->assertStatus(401);

    $this->postJson('/webhooks/xendit', [
        'id' => $payment->external_id,
        'status' => 'PAID',
    ])->assertStatus(401);

    expect($order->fresh()->status)->toBe(OrderStatus::AwaitingPayment)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

it('ignores a webhook for an unknown external id', function () {
    [$order, $payment] = xenditOrder();

    $this->postJson('/webhooks/xendit', [
        'id' => 'does-not-exist',
        'status' => 'PAID',
    ], ['x-callback-token' => 'wh_secret_token'])->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::AwaitingPayment);
});

it('an expired xendit webhook cancels the order and restocks it', function () {
    [$order, $payment] = xenditOrder(stock: 10, qty: 2);

    $this->postJson('/webhooks/xendit', [
        'id' => $payment->external_id,
        'status' => 'EXPIRED',
    ], ['x-callback-token' => 'wh_secret_token'])->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Expired)
        ->and($order->items->first()->product->fresh()->stock)->toBe(10);
});

it('404s the webhook route when ecommerce is off', function () {
    Setting::set('ecommerce', 'false');
    Setting::flushCache();

    $this->postJson('/webhooks/xendit', ['id' => 'x', 'status' => 'PAID'], ['x-callback-token' => 'wh_secret_token'])
        ->assertNotFound();
});

it('the reconcile command updates a stale pending payment', function () {
    [$order, $payment] = xenditOrder();
    $payment->forceFill(['created_at' => now()->subMinutes(10)])->save();

    Http::fake([
        '*/v2/invoices/*' => Http::response(['id' => $payment->external_id, 'status' => 'PAID'], 200),
    ]);

    $this->artisan(ReconcileXenditPayments::class)->assertSuccessful();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('the reconcile command leaves a fresh pending payment untouched', function () {
    [$order, $payment] = xenditOrder();

    Http::fake([
        '*/v2/invoices/*' => Http::response(['id' => $payment->external_id, 'status' => 'PAID'], 200),
    ]);

    $this->artisan(ReconcileXenditPayments::class)->assertSuccessful();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

it('admin can re-check a pending xendit payment from the order detail screen', function () {
    [$order, $payment] = xenditOrder();

    Http::fake([
        '*/v2/invoices/*' => Http::response(['id' => $payment->external_id, 'status' => 'PAID'], 200),
    ]);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(OrderDetail::class, ['id' => $order->id])
        ->call('recheckPayment', $payment->id);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($order->fresh()->status)->toBe(OrderStatus::Paid);
});
