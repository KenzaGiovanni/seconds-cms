<?php

use App\Support\Money;

it('formats IDR as whole rupiah with thousands separators', function () {
    expect(Money::format(1500000))->toBe('Rp 1.500.000')
        ->and(Money::format(0))->toBe('Rp 0')
        ->and(Money::format(50000, 'IDR'))->toBe('Rp 50.000');
});

it('treats other zero-decimal currencies as whole units', function () {
    expect(Money::format(1200, 'JPY'))->toBe('JPY 1,200')
        ->and(Money::isZeroDecimal('VND'))->toBeTrue();
});

it('formats two-decimal currencies from minor units', function () {
    expect(Money::format(1999, 'USD'))->toBe('USD 19.99')
        ->and(Money::isZeroDecimal('USD'))->toBeFalse();
});
