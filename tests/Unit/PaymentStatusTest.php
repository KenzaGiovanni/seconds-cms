<?php

use App\Enums\PaymentStatus;

it('reports settled states', function () {
    expect(PaymentStatus::Paid->isSettled())->toBeTrue()
        ->and(PaymentStatus::Refunded->isSettled())->toBeTrue()
        ->and(PaymentStatus::Pending->isSettled())->toBeFalse()
        ->and(PaymentStatus::Submitted->isSettled())->toBeFalse();
});

it('reports open states eligible for paid/expired', function () {
    expect(PaymentStatus::Pending->isOpen())->toBeTrue()
        ->and(PaymentStatus::Submitted->isOpen())->toBeTrue()
        ->and(PaymentStatus::Paid->isOpen())->toBeFalse()
        ->and(PaymentStatus::Expired->isOpen())->toBeFalse();
});

it('stops the expiry clock once the customer has acted', function () {
    expect(PaymentStatus::Submitted->stopsExpiryClock())->toBeTrue()
        ->and(PaymentStatus::Paid->stopsExpiryClock())->toBeTrue()
        ->and(PaymentStatus::Pending->stopsExpiryClock())->toBeFalse();
});
