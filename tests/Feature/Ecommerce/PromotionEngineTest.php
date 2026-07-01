<?php

use App\Models\Coupon;
use App\Models\Promotion;
use App\Support\DiscountCalculator;

function makePromotion(array $attrs = []): Promotion
{
    return Promotion::create(array_merge([
        'name' => 'Test Promo',
        'type' => 'automatic',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'active' => true,
    ], $attrs));
}

function calc(array $lines, ?string $code = null)
{
    return app(DiscountCalculator::class)->calculate($lines, $code);
}

it('applies a percentage discount across the whole cart', function () {
    makePromotion(['discount_type' => 'percentage', 'discount_value' => 20]);

    $result = calc([['price' => 100000, 'qty' => 2]]);

    // 20% off each of 2 units at 100000 = 40000.
    expect($result->discountTotal)->toBe(40000)
        ->and($result->discountUnits)->toBe(2);
});

it('applies a fixed per-unit discount capped at the unit price', function () {
    makePromotion(['discount_type' => 'fixed', 'discount_value' => 15000]);

    $result = calc([
        ['price' => 100000, 'qty' => 1],
        ['price' => 10000, 'qty' => 1],  // fixed 15000 caps at 10000
    ]);

    expect($result->discountTotal)->toBe(15000 + 10000);
});

it('returns no discount when there is no active promotion', function () {
    $result = calc([['price' => 100000, 'qty' => 1]]);

    expect($result->hasDiscount())->toBeFalse()
        ->and($result->discountTotal)->toBe(0);
});

it('ignores an inactive promotion', function () {
    makePromotion(['active' => false, 'discount_value' => 50]);

    expect(calc([['price' => 100000, 'qty' => 1]])->discountTotal)->toBe(0);
});

// --- min items threshold ---

it('requires the minimum number of items to qualify', function () {
    makePromotion(['discount_value' => 10, 'min_items' => 5]);

    expect(calc([['price' => 100000, 'qty' => 4]])->discountTotal)->toBe(0);
    expect(calc([['price' => 100000, 'qty' => 5]])->discountTotal)->toBe(50000);
});

// --- per-order item cap: "buy 6, the 6th is full price" ---

it('caps the number of discounted units per order', function () {
    makePromotion(['discount_type' => 'fixed', 'discount_value' => 10000, 'max_discounted_items' => 5]);

    // 6 units, only 5 discounted at 10000 each.
    $result = calc([['price' => 100000, 'qty' => 6]]);

    expect($result->discountTotal)->toBe(50000)
        ->and($result->discountUnits)->toBe(5);
});

// --- global quota (counted in discounted units) ---

it('limits discounted units to the remaining global quota', function () {
    // Quota 10, already 9 consumed -> only 1 unit left to discount.
    makePromotion([
        'discount_type' => 'fixed', 'discount_value' => 10000,
        'usage_quota' => 10, 'usage_count' => 9,
    ]);

    $result = calc([['price' => 100000, 'qty' => 2]]);

    expect($result->discountTotal)->toBe(10000)
        ->and($result->discountUnits)->toBe(1);
});

it('gives no discount once the quota is exhausted', function () {
    makePromotion(['usage_quota' => 10, 'usage_count' => 10]);

    expect(calc([['price' => 100000, 'qty' => 3]])->discountTotal)->toBe(0);
});

// --- discounts the highest-priced units first ---

it('discounts the most expensive units first when capped', function () {
    makePromotion(['discount_type' => 'percentage', 'discount_value' => 50, 'max_discounted_items' => 1]);

    $result = calc([
        ['price' => 30000, 'qty' => 1],
        ['price' => 200000, 'qty' => 1],
    ]);

    // Only 1 unit discounted, should be the 200000 one: 50% = 100000.
    expect($result->discountTotal)->toBe(100000);
});

// --- schedule: day of week + time window ---

it('respects the day-of-week restriction', function () {
    $today = now()->dayOfWeek;
    $notToday = ($today + 1) % 7;

    makePromotion(['discount_value' => 10, 'days_of_week' => [$notToday]]);

    expect(calc([['price' => 100000, 'qty' => 1]])->discountTotal)->toBe(0);
});

it('applies on an allowed day of week', function () {
    makePromotion(['discount_value' => 10, 'days_of_week' => [now()->dayOfWeek]]);

    expect(calc([['price' => 100000, 'qty' => 1]])->discountTotal)->toBe(10000);
});

it('respects the daily time window', function () {
    // A window that does not include now.
    $start = now()->addHours(2)->format('H:i:s');
    $end = now()->addHours(3)->format('H:i:s');

    makePromotion(['discount_value' => 10, 'time_start' => $start, 'time_end' => $end]);

    expect(calc([['price' => 100000, 'qty' => 1]])->discountTotal)->toBe(0);
});

it('respects the active date range', function () {
    makePromotion([
        'discount_value' => 10,
        'starts_at' => now()->addDays(3)->toDateString(),
        'ends_at' => now()->addDays(5)->toDateString(),
    ]);

    expect(calc([['price' => 100000, 'qty' => 1]])->discountTotal)->toBe(0);
});

// --- coupons ---

it('does not apply a coupon promotion without the code', function () {
    $promo = makePromotion(['type' => 'coupon', 'discount_value' => 25]);
    Coupon::create(['promotion_id' => $promo->id, 'code' => 'SAVE25']);

    expect(calc([['price' => 100000, 'qty' => 1]])->discountTotal)->toBe(0);
});

it('applies a coupon promotion when the code is provided', function () {
    $promo = makePromotion(['type' => 'coupon', 'discount_type' => 'percentage', 'discount_value' => 25]);
    Coupon::create(['promotion_id' => $promo->id, 'code' => 'SAVE25']);

    $result = calc([['price' => 100000, 'qty' => 1]], 'SAVE25');

    expect($result->discountTotal)->toBe(25000)
        ->and($result->coupon)->not->toBeNull()
        ->and($result->coupon->code)->toBe('SAVE25');
});

it('matches coupon codes case-insensitively', function () {
    $promo = makePromotion(['type' => 'coupon', 'discount_value' => 10]);
    Coupon::create(['promotion_id' => $promo->id, 'code' => 'HELLO']);

    expect(calc([['price' => 100000, 'qty' => 1]], 'hello')->discountTotal)->toBe(10000);
});

it('rejects a coupon that has hit its use limit', function () {
    $promo = makePromotion(['type' => 'coupon', 'discount_value' => 10]);
    Coupon::create(['promotion_id' => $promo->id, 'code' => 'USEDUP', 'max_uses' => 2, 'used_count' => 2]);

    expect(calc([['price' => 100000, 'qty' => 1]], 'USEDUP')->discountTotal)->toBe(0);
});

it('ignores an unknown coupon code', function () {
    expect(calc([['price' => 100000, 'qty' => 1]], 'NOPE')->hasDiscount())->toBeFalse();
});

// --- best-wins between auto + coupon ---

it('picks the larger of an automatic discount and a coupon', function () {
    makePromotion(['discount_type' => 'percentage', 'discount_value' => 10]); // auto: 10% = 10000
    $couponPromo = makePromotion(['type' => 'coupon', 'discount_type' => 'percentage', 'discount_value' => 30]);
    Coupon::create(['promotion_id' => $couponPromo->id, 'code' => 'BIG30']);

    $result = calc([['price' => 100000, 'qty' => 1]], 'BIG30');

    // Coupon 30% (30000) beats auto 10% (10000); never combined.
    expect($result->discountTotal)->toBe(30000)
        ->and($result->coupon->code)->toBe('BIG30');
});

it('keeps the automatic discount when it beats the coupon', function () {
    makePromotion(['discount_type' => 'percentage', 'discount_value' => 40]); // auto 40%
    $couponPromo = makePromotion(['type' => 'coupon', 'discount_type' => 'percentage', 'discount_value' => 15]);
    Coupon::create(['promotion_id' => $couponPromo->id, 'code' => 'SMALL15']);

    $result = calc([['price' => 100000, 'qty' => 1]], 'SMALL15');

    expect($result->discountTotal)->toBe(40000)
        ->and($result->coupon)->toBeNull();
});
