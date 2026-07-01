<?php

namespace App\Enums;

/**
 * The payment mode backing an order's payments. Selected by the
 * `payment_provider` setting and stored in `payments.gateway`.
 *  - Manual : bank transfer + proof-of-payment upload + admin verification.
 *             The DEFAULT - works with zero integration.
 *  - Xendit : hosted gateway (VA/QRIS/e-wallet/card). Opt-in; needs keys.
 */
enum PaymentProvider: string
{
    case Manual = 'manual';
    case Xendit = 'xendit';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual bank transfer',
            self::Xendit => 'Xendit',
        };
    }

    public static function default(): self
    {
        return self::Manual;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $p) => $p->value, self::cases());
    }
}
