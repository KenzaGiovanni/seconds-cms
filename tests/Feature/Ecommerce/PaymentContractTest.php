<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Payments\ManualGateway;
use App\Payments\PaymentEvent;
use App\Payments\PaymentService;
use Illuminate\Support\Str;

/** Build an order sitting in awaiting_payment with `$qty` of stock reserved. */
function reservedOrder(int $stock = 10, int $qty = 2): Order
{
    $product = Product::create([
        'name' => 'Kettle', 'slug' => 'kettle-'.Str::lower(Str::random(6)), 'type' => 'simple',
        'status' => 'published', 'price' => 50000, 'stock_policy' => 'deny', 'stock' => $stock, 'sku' => 'K1',
    ]);

    $product->decrementStock($qty); // checkout reserves stock up front

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

    return $order->fresh('items');
}

it('uses the manual gateway as the default provider', function () {
    $svc = app(PaymentService::class);

    expect($svc->provider())->toBe(PaymentProvider::Manual)
        ->and($svc->gateway())->toBeInstanceOf(ManualGateway::class)
        ->and($svc->gateway()->supportedMethods())->toBe([PaymentMethod::BankTransfer])
        ->and($svc->gateway()->requiresRedirect())->toBeFalse();
});

it('initiate stamps the payment window and creates a pending manual payment', function () {
    $order = reservedOrder();
    $intent = app(PaymentService::class)->initiate($order);

    expect($intent->requiresRedirect())->toBeFalse()
        ->and($intent->payment->gateway)->toBe(PaymentProvider::Manual)
        ->and($intent->payment->method)->toBe(PaymentMethod::BankTransfer)
        ->and($intent->payment->status)->toBe(PaymentStatus::Pending)
        ->and($intent->payment->amount)->toBe(100000)
        ->and($order->fresh()->payment_due_at)->not->toBeNull();
});

it('confirming a manual payment marks the order paid, records the verifier, and is idempotent', function () {
    $order = reservedOrder();
    $admin = User::factory()->create();
    $payment = app(PaymentService::class)->initiate($order)->payment;

    app(PaymentService::class)->confirmManual($payment, $admin);

    $order->refresh();
    $payment->refresh();
    $firstPaidAt = $payment->paid_at;

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->verified_by)->toBe($admin->id)
        ->and($payment->paid_at)->not->toBeNull();

    // Second confirm: no second transition, timestamp unchanged.
    app(PaymentService::class)->confirmManual($payment, $admin);
    $payment->refresh();

    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->paid_at->equalTo($firstPaidAt))->toBeTrue();
});

it('moves a manual payment to submitted when proof is uploaded (stops the clock)', function () {
    $order = reservedOrder();
    $payment = app(PaymentService::class)->initiate($order)->payment;

    app(PaymentService::class)->submitProof($payment, 'proofs/receipt.jpg', 'BCA 12/07 08:14');
    $payment->refresh();

    expect($payment->status)->toBe(PaymentStatus::Submitted)
        ->and($payment->proof_path)->toBe('proofs/receipt.jpg')
        ->and($payment->payer_reference)->toBe('BCA 12/07 08:14')
        ->and($payment->proof_uploaded_at)->not->toBeNull();
});

it('rejecting submitted proof returns the payment to pending with a reason', function () {
    $order = reservedOrder();
    $payment = app(PaymentService::class)->initiate($order)->payment;
    app(PaymentService::class)->submitProof($payment, 'proofs/x.jpg');

    app(PaymentService::class)->rejectManual($payment, 'Amount does not match');
    $payment->refresh();

    expect($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->rejection_reason)->toBe('Amount does not match');
});

it('applies a paid webhook event idempotently and monotonically', function () {
    $order = reservedOrder();
    $payment = Payment::create([
        'order_id' => $order->id, 'gateway' => PaymentProvider::Xendit, 'method' => PaymentMethod::VirtualAccount,
        'external_id' => 'inv_1', 'status' => PaymentStatus::Pending, 'amount' => 100000, 'currency' => 'IDR',
    ]);
    $svc = app(PaymentService::class);

    $svc->applyEvent(new PaymentEvent('inv_1', PaymentStatus::Paid, 'sig-1', ['event' => 'paid']));

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Paid);

    $paidAt = $payment->fresh()->paid_at;

    // Duplicate paid event: no change.
    $svc->applyEvent(new PaymentEvent('inv_1', PaymentStatus::Paid, 'sig-1', []));
    expect($payment->fresh()->paid_at->equalTo($paidAt))->toBeTrue();

    // Out-of-order expired event after paid: ignored, order stays paid.
    $svc->applyEvent(new PaymentEvent('inv_1', PaymentStatus::Expired, 'sig-2', []));
    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Paid);
});

it('ignores a webhook event for an unknown external id', function () {
    $order = reservedOrder();

    app(PaymentService::class)->applyEvent(new PaymentEvent('does-not-exist', PaymentStatus::Paid, 'sig', []));

    expect($order->fresh()->status)->toBe(OrderStatus::AwaitingPayment);
});

it('expires an overdue unpaid order and returns its reserved stock', function () {
    $order = reservedOrder(stock: 10, qty: 2); // stock now 8
    $svc = app(PaymentService::class);
    $payment = $svc->initiate($order)->payment;
    $order->update(['payment_due_at' => now()->subMinute()]);

    expect($svc->expireOverdue())->toBe(1);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Cancelled)
        ->and($order->cancellation_reason)->toBe('payment_expired')
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Expired)
        ->and($order->items->first()->product->fresh()->stock)->toBe(10); // restocked
});

it('does not expire an order that is still within its payment window', function () {
    $order = reservedOrder();
    $svc = app(PaymentService::class);
    $svc->initiate($order); // due in the future

    expect($svc->expireOverdue())->toBe(0)
        ->and($order->fresh()->status)->toBe(OrderStatus::AwaitingPayment);
});

it('does not expire an overdue order once proof has been submitted', function () {
    $order = reservedOrder();
    $svc = app(PaymentService::class);
    $payment = $svc->initiate($order)->payment;
    $svc->submitProof($payment, 'proofs/x.jpg');
    $order->update(['payment_due_at' => now()->subMinute()]);

    expect($svc->expireOverdue())->toBe(0)
        ->and($order->fresh()->status)->toBe(OrderStatus::AwaitingPayment)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Submitted);
});
