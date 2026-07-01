<?php

namespace App\Support;

use App\Enums\PaymentProvider;
use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * Typed accessors over the payment-related site settings: which provider is
 * active, how long a customer has to pay, and the manual bank-transfer details.
 * Keys live in the shared `settings` table (see SiteSettings for the pattern).
 */
class PaymentSettings
{
    public const DEFAULT_WINDOW_MINUTES = 120;

    public static function provider(): PaymentProvider
    {
        return PaymentProvider::tryFrom((string) Setting::get('payment_provider', PaymentProvider::default()->value))
            ?? PaymentProvider::default();
    }

    public static function setProvider(PaymentProvider $provider): void
    {
        Setting::set('payment_provider', $provider->value);
    }

    /** Minutes a customer has to complete/submit payment before the order expires. */
    public static function windowMinutes(): int
    {
        return max(1, (int) (Setting::get('payment_window_minutes') ?: self::DEFAULT_WINDOW_MINUTES));
    }

    public static function dueAt(?Carbon $from = null): Carbon
    {
        return ($from ?? now())->copy()->addMinutes(self::windowMinutes());
    }

    /**
     * Manual bank-transfer details shown to the customer at checkout.
     *
     * @return array{bank_name: string, account_number: string, account_holder: string, instructions: string}
     */
    public static function bankDetails(): array
    {
        return [
            'bank_name' => (string) Setting::get('bank_name', ''),
            'account_number' => (string) Setting::get('bank_account_number', ''),
            'account_holder' => (string) Setting::get('bank_account_holder', ''),
            'instructions' => (string) Setting::get('bank_instructions', ''),
        ];
    }
}
