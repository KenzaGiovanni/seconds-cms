<?php

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransition;
use App\Models\Order;

function makeOrder(OrderStatus $status = OrderStatus::Pending): Order
{
    return Order::create([
        'status' => $status,
        'email' => 'buyer@example.com',
        'currency' => 'IDR',
    ]);
}

it('auto-generates a unique order number on create', function () {
    $order = makeOrder();

    expect($order->number)->toStartWith('SEC-')
        ->and($order->number)->not->toBeEmpty();
});

it('snapshots line items and recalculates totals', function () {
    $order = makeOrder();

    $order->items()->create([
        'name' => 'Kopi Susu', 'sku' => 'KS', 'unit_price' => 25000, 'quantity' => 2,
        'line_total' => 50000, 'currency' => 'IDR', 'options' => ['size' => 'L'],
    ]);
    $order->items()->create([
        'name' => 'Croissant', 'unit_price' => 18000, 'quantity' => 1,
        'line_total' => 18000, 'currency' => 'IDR',
    ]);

    $order->load('items');
    $order->shipping_total = 10000;
    $order->recalculateTotals();
    $order->save();

    expect($order->subtotal)->toBe(68000)
        ->and($order->total)->toBe(78000)
        ->and($order->formattedTotal())->toBe('Rp 78.000')
        ->and($order->items->first()->options)->toBe(['size' => 'L']);
});

it('transitions through the happy path stamping timestamps', function () {
    $order = makeOrder(OrderStatus::AwaitingPayment);

    $order->transitionTo(OrderStatus::Paid);
    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->paid_at)->not->toBeNull();

    $order->transitionTo(OrderStatus::Fulfilled);
    expect($order->fulfilled_at)->not->toBeNull();

    $order->transitionTo(OrderStatus::Completed);
    expect($order->fresh()->status)->toBe(OrderStatus::Completed)
        ->and($order->completed_at)->not->toBeNull();
});

it('throws on an illegal transition and leaves the status unchanged', function () {
    $order = makeOrder(OrderStatus::Pending);

    expect(fn () => $order->transitionTo(OrderStatus::Paid))
        ->toThrow(InvalidOrderTransition::class);

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

it('can cancel before payment', function () {
    $order = makeOrder(OrderStatus::AwaitingPayment);

    $order->transitionTo(OrderStatus::Cancelled);

    expect($order->status)->toBe(OrderStatus::Cancelled)
        ->and($order->cancelled_at)->not->toBeNull()
        ->and($order->status->isTerminal())->toBeTrue();
});
