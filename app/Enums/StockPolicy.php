<?php

namespace App\Enums;

/**
 * How a product/variant handles inventory:
 *  - None      : stock is not tracked; always purchasable.
 *  - Deny      : stock is tracked; purchase blocked once it hits zero.
 *  - Backorder : stock is tracked but purchase is allowed past zero (goes negative).
 */
enum StockPolicy: string
{
    case None = 'none';
    case Deny = 'deny';
    case Backorder = 'backorder';

    public function tracksStock(): bool
    {
        return $this !== self::None;
    }

    public function label(): string
    {
        return match ($this) {
            self::None => "Don't track stock",
            self::Deny => 'Deny when out of stock',
            self::Backorder => 'Allow backorder',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $p) => $p->value, self::cases());
    }
}
