<?php

namespace App\Enums;

use App\Support\Money;

/**
 * The shape of a discount, applied per eligible unit:
 *  - Percentage : `value`% off each eligible unit's price (value is 1-100).
 *  - Fixed      : `value` (integer minor units) off each eligible unit,
 *                 capped at the unit price so a line never goes negative.
 */
enum DiscountType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Percentage (%)',
            self::Fixed => 'Fixed amount',
        };
    }

    /** Discount applied to a single unit at the given price (integer minor units). */
    public function discountForUnit(int $unitPrice, int $value): int
    {
        return match ($this) {
            self::Percentage => (int) floor($unitPrice * $value / 100),
            self::Fixed => min($value, $unitPrice),
        };
    }

    /** Human-readable value, e.g. "20%" or "Rp 10.000". */
    public function formatValue(int $value, string $currency = Money::DEFAULT_CURRENCY): string
    {
        return match ($this) {
            self::Percentage => $value.'%',
            self::Fixed => Money::format($value, $currency),
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}
