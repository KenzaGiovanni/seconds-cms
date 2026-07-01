<?php

namespace App\Enums;

/**
 * How a promotion is triggered:
 *  - Automatic : applied to any qualifying cart with no code needed.
 *  - Coupon    : applied only when a customer enters one of its codes.
 */
enum PromotionType: string
{
    case Automatic = 'automatic';
    case Coupon = 'coupon';

    public function label(): string
    {
        return match ($this) {
            self::Automatic => 'Automatic',
            self::Coupon => 'Coupon code',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}
