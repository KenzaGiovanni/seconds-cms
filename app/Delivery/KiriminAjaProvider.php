<?php

namespace App\Delivery;

use App\Contracts\DeliveryProvider;
use App\Enums\ShipmentStatus;
use App\Enums\ShippingProvider;
use App\Models\Order;
use App\Models\Shipment;
use App\Support\DeliverySettings;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use KiriminAja\Models\PackageData;
use KiriminAja\Models\RequestPickupData;
use KiriminAja\Models\ShippingPriceData;

/**
 * The real KiriminAja integration, behind the DeliveryProvider contract. Wraps
 * the official `kiriminaja/kiriminaja-php` SDK via KiriminAjaClient (injected,
 * not called statically, so it is mockable in tests).
 *
 * ⚠️ KiriminAja's `request_pickup` endpoint is a SINGLE call that both books
 * the courier AND schedules the pickup (it requires a `schedule` datetime up
 * front and returns the AWB/tracking number in the same response) - there is
 * no separate "reschedule" endpoint in this SDK. So book() does the real,
 * full external call (creates the courier order), and schedulePickup() is a
 * no-op here - kept for interface parity with providers that DO separate the
 * two steps. This is a considered v1 design call, not an oversight.
 *
 * ⚠️ KiriminAja's inbound webhook payload shape is not documented in the SDK
 * (it only wraps outbound API calls) - handleWebhook()'s field mapping is a
 * best-effort guess (order_id/awb + status + note) and MUST be verified
 * against a real payload before this goes live with production traffic.
 */
class KiriminAjaProvider implements DeliveryProvider
{
    public function __construct(private readonly KiriminAjaClient $client) {}

    public function provider(): ShippingProvider
    {
        return ShippingProvider::Kiriminaja;
    }

    public function requiresManualTracking(): bool
    {
        return false;
    }

    /** @return list<RateQuote> */
    public function rates(Address $origin, Address $destination, Parcel $parcel): array
    {
        $this->authenticate();

        if (! $origin->subdistrictId || ! $destination->subdistrictId) {
            // KiriminAja prices by sub-district id on both ends; without one
            // there is nothing to quote. ShipmentService falls back to flat.
            return [];
        }

        $data = new ShippingPriceData;
        $data->origin = $origin->subdistrictId;
        $data->destination = $destination->subdistrictId;
        $data->weight = max(1, $parcel->weightGrams);
        $data->item_value = $parcel->itemValue;
        $data->length = $parcel->lengthCm ?? 0;
        $data->width = $parcel->widthCm ?? 0;
        $data->height = $parcel->heightCm ?? 0;

        $enabled = DeliverySettings::enabledCouriers();
        if ($enabled !== []) {
            $data->courier = $enabled;
        }

        $result = $this->client->price($data);

        return $this->mapRateResults($result['results'] ?? []);
    }

    /**
     * Book the courier. Calls KiriminAja's request_pickup (which also
     * schedules the pickup - see class docblock) and persists the resulting
     * AWB/tracking number.
     */
    public function book(Order $order, RateChoice $choice): Shipment
    {
        $this->authenticate();

        $origin = DeliverySettings::origin();
        $destination = $this->destinationFrom($order);

        $pickup = new RequestPickupData;
        $pickup->address = $origin->address;
        $pickup->phone = $origin->phone;
        $pickup->name = $origin->name;
        $pickup->zipcode = (string) ($origin->postalCode ?? '');
        $pickup->kecamatan_id = (int) $origin->subdistrictId;
        $pickup->schedule = now()->addHour()->format('Y-m-d H:i:s');
        $pickup->platform_name = 'seconds-cms';

        $package = new PackageData;
        $package->order_id = $order->number;
        $package->destination_name = $destination->name;
        $package->destination_phone = $destination->phone;
        $package->destination_address = $destination->address;
        $package->destination_kecamatan_id = (int) ($destination->subdistrictId ?? 0);
        $package->destination_zipcode = (string) ($destination->postalCode ?? '');
        $package->weight = DeliverySettings::defaultWeightGrams();
        $package->item_value = (int) $order->subtotal;
        $package->shipping_cost = $choice->cost;
        $package->service = $choice->courier;
        $package->service_type = $choice->serviceCode;
        $package->item_name = 'Order '.$order->number;

        $pickup->packages->add($package);

        $result = $this->client->requestPickup($pickup);

        return Shipment::create([
            'order_id' => $order->id,
            'provider' => ShippingProvider::Kiriminaja,
            'courier' => $choice->courier,
            'service_code' => $choice->serviceCode,
            'external_id' => (string) ($result['order_id'] ?? $order->number),
            'tracking_number' => (string) ($result['awb'] ?? $result['resi'] ?? ''),
            'status' => ShipmentStatus::Booked,
            'cost' => $choice->cost,
            'currency' => $choice->currency,
            'destination' => $order->shipping_address,
            'raw_payload' => $result,
            'booked_at' => now(),
        ]);
    }

    public function schedulePickup(Shipment $shipment, ?string $window = null): void
    {
        // No-op: request_pickup (book()) already schedules the pickup in one
        // call - see the class docblock.
    }

    /** @return list<TrackingUpdate> */
    public function track(Shipment $shipment): array
    {
        if (! $shipment->external_id) {
            return [];
        }

        $this->authenticate();

        $result = $this->client->tracking($shipment->external_id);

        return $this->mapTrackingHistory($result['history'] ?? $result['tracking'] ?? []);
    }

    public function handleWebhook(Request $request): TrackingEvent
    {
        $expected = DeliverySettings::kiriminajaKeys()['webhook_token'];
        $received = $request->header('X-Kiriminaja-Token') ?? $request->query('token', '');

        if ($expected === '' || ! hash_equals($expected, (string) $received)) {
            throw new \RuntimeException('Invalid or missing KiriminAja webhook token.');
        }

        $payload = $request->all();
        $externalId = (string) ($payload['order_id'] ?? $payload['awb'] ?? '');

        if ($externalId === '') {
            throw new \RuntimeException('KiriminAja webhook payload missing order_id/awb.');
        }

        $status = $this->mapWebhookStatus((string) ($payload['status'] ?? ''));

        return new TrackingEvent(
            externalId: $externalId,
            status: $status,
            signature: (string) ($payload['status'] ?? '').':'.($payload['updated_at'] ?? now()->toIso8601String()),
            rawPayload: $payload,
        );
    }

    /** @return list<RateQuote> */
    private function mapRateResults(array $results): array
    {
        $quotes = [];

        foreach ($results as $result) {
            if (! is_array($result) || ! isset($result['courier'], $result['service'], $result['cost'])) {
                continue; // defensively skip malformed entries - unverified real payload shape
            }

            $quotes[] = new RateQuote(
                courier: (string) $result['courier'],
                serviceCode: (string) $result['service'],
                serviceName: (string) ($result['service_name'] ?? strtoupper($result['courier'].' '.$result['service'])),
                cost: (int) $result['cost'],
                etaText: isset($result['etd']) ? (string) $result['etd'] : null,
            );
        }

        return $quotes;
    }

    /** @return list<TrackingUpdate> */
    private function mapTrackingHistory(array $history): array
    {
        $updates = [];

        foreach ($history as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $status = $this->mapWebhookStatus((string) ($entry['status'] ?? ''));
            $updates[] = new TrackingUpdate(
                status: $status,
                description: (string) ($entry['description'] ?? $entry['note'] ?? $status->label()),
                occurredAt: isset($entry['datetime']) ? Carbon::parse($entry['datetime']) : null,
            );
        }

        return $updates;
    }

    /**
     * Best-effort mapping of KiriminAja's tracking status strings to our
     * ShipmentStatus. Unverified against a real payload (no API key
     * available at build time) - revisit once live traffic confirms the
     * actual vocabulary.
     */
    private function mapWebhookStatus(string $raw): ShipmentStatus
    {
        return match (strtolower($raw)) {
            'picked_up', 'pickup', 'pickup_success' => ShipmentStatus::PickedUp,
            'on_process', 'in_transit', 'shipping', 'otw' => ShipmentStatus::InTransit,
            'delivered', 'success', 'received' => ShipmentStatus::Delivered,
            'cancelled', 'canceled', 'cancel' => ShipmentStatus::Cancelled,
            'returned', 'return', 'retur' => ShipmentStatus::Returned,
            default => ShipmentStatus::Booked,
        };
    }

    private function destinationFrom(Order $order): Address
    {
        $addr = $order->shipping_address ?? [];

        return new Address(
            name: $order->customer_name ?? (string) ($addr['name'] ?? ''),
            phone: $order->phone ?? (string) ($addr['phone'] ?? ''),
            address: (string) ($addr['address_line'] ?? ($addr['address'] ?? '')),
            subdistrictId: isset($addr['subdistrict_id']) ? (int) $addr['subdistrict_id'] : null,
            city: (string) ($addr['city'] ?? ''),
            postalCode: (string) ($addr['postal_code'] ?? ($addr['postal'] ?? '')),
        );
    }

    private function authenticate(): void
    {
        $keys = DeliverySettings::kiriminajaKeys();
        $this->client->configure($keys['api_key'], $keys['mode'] ?: 'staging');
    }
}
