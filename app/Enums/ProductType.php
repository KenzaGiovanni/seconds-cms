<?php

namespace App\Enums;

/**
 * A product is either `simple` (one price/sku/stock on the product itself) or
 * `variable` (price/sku/stock live on its variants). Mirrors WooCommerce.
 */
enum ProductType: string
{
    case Simple = 'simple';
    case Variable = 'variable';

    public function label(): string
    {
        return match ($this) {
            self::Simple => 'Simple',
            self::Variable => 'Variable',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}
