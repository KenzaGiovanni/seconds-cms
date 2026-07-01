<?php

namespace App\Support;

use App\Models\Coupon;
use App\Models\Promotion;

/**
 * The outcome of running the discount engine over a cart: how much comes off,
 * which promotion/coupon won (best-wins), and how many units were discounted
 * (needed to release the quota if the order is later cancelled).
 */
class DiscountResult
{
    public function __construct(
        public readonly int $discountTotal = 0,
        public readonly ?Promotion $promotion = null,
        public readonly ?Coupon $coupon = null,
        public readonly int $discountUnits = 0,
        public readonly string $label = '',
    ) {}

    public static function none(): self
    {
        return new self;
    }

    public function hasDiscount(): bool
    {
        return $this->discountTotal > 0;
    }
}
