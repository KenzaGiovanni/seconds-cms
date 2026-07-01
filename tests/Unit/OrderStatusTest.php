<?php

use App\Enums\OrderStatus;

it('allows the happy-path transitions', function () {
    expect(OrderStatus::Pending->canTransitionTo(OrderStatus::AwaitingPayment))->toBeTrue()
        ->and(OrderStatus::AwaitingPayment->canTransitionTo(OrderStatus::Paid))->toBeTrue()
        ->and(OrderStatus::Paid->canTransitionTo(OrderStatus::Fulfilled))->toBeTrue()
        ->and(OrderStatus::Fulfilled->canTransitionTo(OrderStatus::Completed))->toBeTrue();
});

it('rejects illegal skips and backward moves', function () {
    expect(OrderStatus::Pending->canTransitionTo(OrderStatus::Paid))->toBeFalse()
        ->and(OrderStatus::AwaitingPayment->canTransitionTo(OrderStatus::Fulfilled))->toBeFalse()
        ->and(OrderStatus::Completed->canTransitionTo(OrderStatus::Paid))->toBeFalse()
        ->and(OrderStatus::Paid->canTransitionTo(OrderStatus::Pending))->toBeFalse();
});

it('allows cancel and refund at the right points', function () {
    expect(OrderStatus::AwaitingPayment->canTransitionTo(OrderStatus::Cancelled))->toBeTrue()
        ->and(OrderStatus::Paid->canTransitionTo(OrderStatus::Refunded))->toBeTrue()
        ->and(OrderStatus::Fulfilled->canTransitionTo(OrderStatus::Refunded))->toBeTrue();
});

it('treats cancelled and refunded as terminal', function () {
    expect(OrderStatus::Cancelled->isTerminal())->toBeTrue()
        ->and(OrderStatus::Refunded->isTerminal())->toBeTrue()
        ->and(OrderStatus::Paid->isTerminal())->toBeFalse();
});

it('flags restock only for pre-fulfilment cancels', function () {
    expect(OrderStatus::AwaitingPayment->shouldRestockOnCancel())->toBeTrue()
        ->and(OrderStatus::Paid->shouldRestockOnCancel())->toBeTrue()
        ->and(OrderStatus::Pending->shouldRestockOnCancel())->toBeFalse();
});
