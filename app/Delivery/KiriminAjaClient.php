<?php

namespace App\Delivery;

use App\Support\ApiLogger;
use KiriminAja\Base\Config\KiriminAjaConfig;
use KiriminAja\Models\RequestPickupData;
use KiriminAja\Models\ShippingPriceData;
use KiriminAja\Responses\ServiceResponse;
use KiriminAja\Services\KiriminAja;

/**
 * Thin wrapper over the KiriminAja SDK's static facade. The SDK exposes plain
 * static methods (not a container-bound service), so this class exists purely
 * to make it injectable and mockable in tests - bind a fake/mock of this class
 * rather than trying to intercept the SDK's internal Guzzle client. Every
 * public method returns the raw `data` payload on success and throws on
 * failure, so callers (KiriminAjaProvider) never touch ServiceResponse.
 *
 * Every call is logged via ApiLogger::kiriminaja() (request + response,
 * success or failure) - see App\Support\ApiLogger.
 */
class KiriminAjaClient
{
    /** Push our persisted credentials into the SDK before every call - keys may
     * be activated/changed at runtime via admin settings, not just env/boot. */
    public function configure(string $apiKey, string $mode, ?string $baseUrl = null): void
    {
        KiriminAjaConfig::setMode($mode);
        KiriminAjaConfig::setApiTokenKey($apiKey);

        if ($baseUrl) {
            KiriminAjaConfig::setBaseUrl($baseUrl);
        }
    }

    /** @return array<string, mixed> */
    public function price(ShippingPriceData $data): array
    {
        return $this->unwrap(ApiLogger::kiriminaja('shipping_price', $data->toArray(), fn () => KiriminAja::getPrice($data)));
    }

    /** @return array<string, mixed> */
    public function requestPickup(RequestPickupData $data): array
    {
        return $this->unwrap(ApiLogger::kiriminaja('request_pickup', $data->toArray(), fn () => KiriminAja::requestPickup($data)));
    }

    /** @return array<string, mixed> */
    public function tracking(string $orderId): array
    {
        return $this->unwrap(ApiLogger::kiriminaja('tracking', ['order_id' => $orderId], fn () => KiriminAja::getTracking($orderId)));
    }

    /** @return array<string, mixed> */
    public function cancel(string $awb, string $reason): array
    {
        return $this->unwrap(ApiLogger::kiriminaja('cancel_shipment', ['awb' => $awb, 'reason' => $reason], fn () => KiriminAja::cancelShipment($awb, $reason)));
    }

    /** @return array<string, mixed> */
    public function creditBalance(): array
    {
        return $this->unwrap(ApiLogger::kiriminaja('credit_balance', [], fn () => KiriminAja::getCreditBalance()));
    }

    /** @return array<string, mixed> */
    public function setCallback(string $url): array
    {
        return $this->unwrap(ApiLogger::kiriminaja('set_callback', ['url' => $url], fn () => KiriminAja::setCallback($url)));
    }

    /** @return array<string, mixed> */
    public function provinces(): array
    {
        return $this->unwrap(ApiLogger::kiriminaja('province', [], fn () => KiriminAja::getProvince()));
    }

    /** @return array<string, mixed> */
    public function cities(int $provinceId): array
    {
        return $this->unwrap(ApiLogger::kiriminaja('city', ['province_id' => $provinceId], fn () => KiriminAja::getCity($provinceId)));
    }

    /** @return array<string, mixed> */
    public function districts(int $cityId): array
    {
        return $this->unwrap(ApiLogger::kiriminaja('district', ['city_id' => $cityId], fn () => KiriminAja::getDistrict($cityId)));
    }

    /** @return array<string, mixed> */
    public function subdistricts(int $districtId): array
    {
        return $this->unwrap(ApiLogger::kiriminaja('subdistrict', ['district_id' => $districtId], fn () => KiriminAja::getSubDistrict($districtId)));
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \RuntimeException on a failed ServiceResponse
     */
    private function unwrap(ServiceResponse $response): array
    {
        if (! $response->status) {
            throw new \RuntimeException($response->message ?: 'KiriminAja request failed.');
        }

        return is_array($response->data) ? $response->data : [];
    }
}
