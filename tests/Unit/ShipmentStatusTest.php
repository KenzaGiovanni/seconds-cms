<?php

use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;

it('allows only legal forward transitions', function () {
    expect(ShipmentStatus::Pending->canTransitionTo(ShipmentStatus::Booked))->toBeTrue();
    expect(ShipmentStatus::Booked->canTransitionTo(ShipmentStatus::PickedUp))->toBeTrue();
    expect(ShipmentStatus::PickedUp->canTransitionTo(ShipmentStatus::Delivered))->toBeTrue();

    // No going backwards.
    expect(ShipmentStatus::PickedUp->canTransitionTo(ShipmentStatus::Booked))->toBeFalse();
    // Delivered/cancelled/returned are terminal.
    expect(ShipmentStatus::Delivered->transitions())->toBe([]);
    expect(ShipmentStatus::Delivered->isTerminal())->toBeTrue();
    expect(ShipmentStatus::Cancelled->isTerminal())->toBeTrue();
    expect(ShipmentStatus::Returned->isTerminal())->toBeTrue();
});

it('ranks the happy path so out-of-order events can be dropped', function () {
    expect(ShipmentStatus::Pending->rank())->toBeLessThan(ShipmentStatus::Booked->rank());
    expect(ShipmentStatus::Booked->rank())->toBeLessThan(ShipmentStatus::PickedUp->rank());
    expect(ShipmentStatus::PickedUp->rank())->toBeLessThan(ShipmentStatus::InTransit->rank());
    expect(ShipmentStatus::InTransit->rank())->toBeLessThan(ShipmentStatus::Delivered->rank());

    expect(ShipmentStatus::Cancelled->isExit())->toBeTrue();
    expect(ShipmentStatus::Returned->isExit())->toBeTrue();
    expect(ShipmentStatus::Delivered->isExit())->toBeFalse();
});

it('maps shipment status to the order fulfilment target', function () {
    expect(ShipmentStatus::Pending->orderFulfilmentTarget())->toBeNull();
    expect(ShipmentStatus::Booked->orderFulfilmentTarget())->toBeNull();
    expect(ShipmentStatus::PickedUp->orderFulfilmentTarget())->toBe(OrderStatus::Fulfilled);
    expect(ShipmentStatus::InTransit->orderFulfilmentTarget())->toBe(OrderStatus::Fulfilled);
    expect(ShipmentStatus::Delivered->orderFulfilmentTarget())->toBe(OrderStatus::Completed);
    expect(ShipmentStatus::Cancelled->orderFulfilmentTarget())->toBeNull();
});
