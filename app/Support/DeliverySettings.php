<?php

namespace App\Support;

use App\Delivery\Address;
use App\Delivery\Parcel;
use App\Enums\ManualDeliveryMode;
use App\Enums\ShippingProvider;
use App\Models\Region\District;
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

    /** How the manual/offline provider prices its one rate option. */
    public static function manualMode(): ManualDeliveryMode
    {
        return ManualDeliveryMode::tryFrom((string) Setting::get('delivery_manual_mode', ManualDeliveryMode::default()->value))
            ?? ManualDeliveryMode::default();
    }

    public static function setManualMode(ManualDeliveryMode $mode): void
    {
        Setting::set('delivery_manual_mode', $mode->value);
    }

    /** Cart subtotal (integer minor units) at/above which manual delivery is free, when FreeShipping mode is active. */
    public static function freeShippingMinimum(): int
    {
        return max(0, (int) Setting::get('delivery_free_shipping_minimum', 0));
    }

    public static function setFreeShippingMinimum(int $minorUnits): void
    {
        Setting::set('delivery_free_shipping_minimum', (string) max(0, $minorUnits));
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

    /**
     * The store's ship-from address for rate + booking calls. `subdistrictId`
     * and `city` are derived from the selected district (region picker), not
     * stored directly - so they stay correct even before/after a KiriminAja
     * reconciliation backfills `kiriminaja_subdistrict_id` on that district.
     */
    public static function origin(): Address
    {
        $district = ($code = self::originDistrictCode()) ? District::with('regency')->find($code) : null;

        return new Address(
            name: (string) Setting::get('delivery_origin_name', (string) Setting::get('site_name', '')),
            phone: (string) Setting::get('delivery_origin_phone', ''),
            address: (string) Setting::get('delivery_origin_address', ''),
            subdistrictId: $district?->kiriminaja_subdistrict_id,
            city: $district?->regency?->name,
            postalCode: (string) Setting::get('delivery_origin_postal', ''),
        );
    }

    /** The locally-selected origin district code (id_districts.code), or null if unset. */
    public static function originDistrictCode(): ?string
    {
        $code = (string) Setting::get('delivery_origin_district_code', '');

        return $code !== '' ? $code : null;
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

    public static function maskedKiriminajaApiKey(): ?string
    {
        $key = self::kiriminajaKeys()['api_key'];

        return $key === '' ? null : str_repeat('•', 8).substr($key, -4);
    }

    /** Courier codes to filter rate quotes to (empty = all couriers KiriminAja offers). */
    public static function enabledCouriers(): array
    {
        $raw = (string) Setting::get('kiriminaja_enabled_couriers', '');

        return $raw === '' ? [] : array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /** @param  list<string>  $couriers */
    public static function setEnabledCouriers(array $couriers): void
    {
        Setting::set('kiriminaja_enabled_couriers', implode(',', $couriers));
    }

    public static function setOrigin(string $name, string $phone, string $address, string $postalCode, ?string $districtCode): void
    {
        Setting::set('delivery_origin_name', $name);
        Setting::set('delivery_origin_phone', $phone);
        Setting::set('delivery_origin_address', $address);
        Setting::set('delivery_origin_postal', $postalCode);
        Setting::set('delivery_origin_district_code', $districtCode ?? '');
    }
}
