<?php

namespace App\Enums;

/**
 * Which delivery mode is active, selected by the `delivery_provider` setting.
 * Mirrors PaymentProvider: `manual` is the first-class default (admin books
 * offline + types the courier/tracking by hand), `kiriminaja` is opt-in and
 * unlocked once its API key is entered and validated.
 */
enum ShippingProvider: string
{
    case Manual = 'manual';
    case Kiriminaja = 'kiriminaja';

    public static function default(): self
    {
        return self::Manual;
    }

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual / offline',
            self::Kiriminaja => 'KiriminAja',
        };
    }
}
