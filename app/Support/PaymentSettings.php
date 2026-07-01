<?php

namespace App\Support;

use App\Enums\PaymentMethod;
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

    /** Persist activated Xendit credentials. Blank values are stored as empty strings, not skipped. */
    public static function setXenditKeys(string $secretKey, string $publicKey, string $webhookToken): void
    {
        Setting::set('xendit_secret_key', $secretKey);
        Setting::set('xendit_public_key', $publicKey);
        Setting::set('xendit_webhook_token', $webhookToken);
    }

    /**
     * Raw Xendit credentials for building requests / verifying webhooks.
     * Falls back to config/services.php (env) when nothing has been activated yet.
     *
     * @return array{secret_key: string, public_key: string, webhook_token: string}
     */
    public static function xenditKeys(): array
    {
        return [
            'secret_key' => (string) (Setting::get('xendit_secret_key') ?: config('services.xendit.secret_key', '')),
            'public_key' => (string) (Setting::get('xendit_public_key') ?: config('services.xendit.public_key', '')),
            'webhook_token' => (string) (Setting::get('xendit_webhook_token') ?: config('services.xendit.webhook_token', '')),
        ];
    }

    /** Last 4 characters only - safe to render in the admin UI. */
    public static function maskedXenditSecretKey(): ?string
    {
        $key = self::xenditKeys()['secret_key'];

        return $key === '' ? null : str_repeat('•', 8).substr($key, -4);
    }

    public static function xenditBaseUrl(): string
    {
        return rtrim((string) config('services.xendit.base_url', 'https://api.xendit.co'), '/');
    }

    /** @return list<PaymentMethod> Xendit methods the admin has enabled (defaults to all of them). */
    public static function xenditEnabledMethods(): array
    {
        $stored = Setting::get('xendit_enabled_methods');

        if ($stored === null || $stored === '') {
            return self::allXenditMethods();
        }

        $methods = array_filter(array_map(
            fn (string $value) => PaymentMethod::tryFrom($value),
            explode(',', $stored),
        ));

        $methods = array_values(array_filter($methods, fn (PaymentMethod $m) => $m->provider() === PaymentProvider::Xendit));

        return $methods ?: self::allXenditMethods();
    }

    /** @param  list<PaymentMethod>  $methods */
    public static function setXenditEnabledMethods(array $methods): void
    {
        $values = array_map(fn (PaymentMethod $m) => $m->value, $methods);
        Setting::set('xendit_enabled_methods', implode(',', $values));
    }

    /** @return list<PaymentMethod> */
    public static function allXenditMethods(): array
    {
        return array_values(array_filter(
            PaymentMethod::cases(),
            fn (PaymentMethod $m) => $m->provider() === PaymentProvider::Xendit,
        ));
    }
}
