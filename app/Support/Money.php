<?php

namespace App\Support;

/**
 * Money is stored as an integer amount in the currency's smallest tracked unit.
 * IDR is effectively zero-decimal, so an IDR amount is whole rupiah (1500000 =
 * Rp 1.500.000). A `currency` column rides alongside every priced row so
 * multi-currency stays possible later; v1 only exposes IDR. No FX here.
 */
class Money
{
    public const DEFAULT_CURRENCY = 'IDR';

    /** Currencies with no minor unit (amount is already the whole unit). */
    private const ZERO_DECIMAL = ['IDR', 'JPY', 'KRW', 'VND'];

    public static function format(int $amount, string $currency = self::DEFAULT_CURRENCY): string
    {
        $currency = strtoupper($currency);

        if ($currency === 'IDR') {
            return 'Rp '.number_format($amount, 0, ',', '.');
        }

        if (in_array($currency, self::ZERO_DECIMAL, true)) {
            return $currency.' '.number_format($amount, 0, '.', ',');
        }

        // Generic: assume 2-decimal minor units (cents).
        return $currency.' '.number_format($amount / 100, 2, '.', ',');
    }

    public static function isZeroDecimal(string $currency): bool
    {
        return in_array(strtoupper($currency), self::ZERO_DECIMAL, true);
    }
}
