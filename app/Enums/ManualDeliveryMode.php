<?php

namespace App\Enums;

/**
 * How the manual/offline delivery provider prices its single rate option.
 * `Flat` always charges `DeliverySettings::flatRate()`. `FreeShipping`
 * charges nothing once the cart subtotal reaches a configured minimum,
 * otherwise falls back to the same flat rate - the common "free shipping
 * over Rp X" pattern.
 */
enum ManualDeliveryMode: string
{
    case Flat = 'flat';
    case FreeShipping = 'free_shipping';

    public static function default(): self
    {
        return self::Flat;
    }

    public function label(): string
    {
        return match ($this) {
            self::Flat => 'Single flat rate',
            self::FreeShipping => 'Free shipping (with minimum)',
        };
    }
}
