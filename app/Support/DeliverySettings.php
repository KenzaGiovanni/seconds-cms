<?php

namespace App\Support;

use App\Delivery\Address;
use App\Delivery\Parcel;
use App\Enums\ShippingProvider;
use App\Models\Setting;

/**
 * Typed accessors over the delivery-related site settings: which provider is
 * active, the store origin address + default parcel used for rate calls, and
 * the flat-rate fallback shown when live rates are unavailable. Keys live in
 * the shared `settings` table (same pattern as PaymentSettings).
 */
class DeliverySettings
{
    public const DEFAULT_WEIGHT_GRAMS = 1000;

    public const DEFAULT_FLAT_RATE = 0;

    public static function provider(): ShippingProvider
    {
        return ShippingProvider::tryFrom((string) Setting::get('delivery_provider', ShippingProvider::default()->value))
            ?? ShippingProvider::default();
    }

    public static function setProvider(ShippingProvider $provider): void
    {
        Setting::set('delivery_provider', $provider->value);
    }

    /** Flat shipping cost (integer minor units) used as the graceful fallback. */
    public static function flatRate(): int
    {
        return max(0, (int) Setting::get('delivery_flat_rate', self::DEFAULT_FLAT_RATE));
    }

    /** Default parcel weight in grams for rate calls when no per-order figure exists. */
    public static function defaultWeightGrams(): int
    {
        return max(1, (int) (Setting::get('delivery_default_weight') ?: self::DEFAULT_WEIGHT_GRAMS));
    }

    public static function defaultParcel(int $itemValue = 0): Parcel
    {
        return new Parcel(
            weightGrams: self::defaultWeightGrams(),
            itemValue: $itemValue,
        );
    }

    /** The store's ship-from address for rate + booking calls. */
    public static function origin(): Address
    {
        return new Address(
            name: (string) Setting::get('delivery_origin_name', (string) Setting::get('site_name', '')),
            phone: (string) Setting::get('delivery_origin_phone', ''),
            address: (string) Setting::get('delivery_origin_address', ''),
            subdistrictId: ($sid = Setting::get('delivery_origin_subdistrict_id')) ? (int) $sid : null,
            city: (string) Setting::get('delivery_origin_city', ''),
            postalCode: (string) Setting::get('delivery_origin_postal', ''),
        );
    }

    /**
     * Raw KiriminAja credentials for building requests / verifying webhooks.
     * Falls back to config/services.php (env) when nothing has been activated yet.
     *
     * @return array{api_key: string, mode: string, webhook_token: string}
     */
    public static function kiriminajaKeys(): array
    {
        return [
            'api_key' => (string) (Setting::get('kiriminaja_api_key') ?: config('services.kiriminaja.api_key', '')),
            'mode' => (string) (Setting::get('kiriminaja_mode') ?: config('services.kiriminaja.mode', 'staging')),
            'webhook_token' => (string) (Setting::get('kiriminaja_webhook_token') ?: config('services.kiriminaja.webhook_token', '')),
        ];
    }

    /** Persist activated KiriminAja credentials. Blank values are stored, not skipped. */
    public static function setKiriminajaKeys(string $apiKey, string $mode, string $webhookToken): void
    {
        Setting::set('kiriminaja_api_key', $apiKey);
        Setting::set('kiriminaja_mode', $mode);
        Setting::set('kiriminaja_webhook_token', $webhookToken);
    }
}
