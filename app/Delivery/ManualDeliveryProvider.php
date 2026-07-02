<?php

namespace App\Delivery;

use App\Contracts\DeliveryProvider;
use App\Enums\ShipmentStatus;
use App\Enums\ShippingProvider;
use App\Models\Order;
use App\Models\Shipment;
use App\Support\DeliverySettings;
use Illuminate\Http\Request;

/**
 * The default delivery mode: offline / manual fulfilment. It offers a single
 * flat-rate option (from Shop settings), and booking simply records the courier
 * + tracking number the admin types in by hand - there is no courier API and no
 * webhook. Status is advanced manually by staff (ShipmentService::advanceManual).
 * First-class, not an afterthought - mirrors ManualGateway on the payment side.
 */
class ManualDeliveryProvider implements DeliveryProvider
{
    public function provider(): ShippingProvider
    {
        return ShippingProvider::Manual;
    }

    public function requiresManualTracking(): bool
    {
        return true;
    }

    /** @return list<RateQuote> */
    public function rates(Address $origin, Address $destination, Parcel $parcel): array
    {
        return [
            new RateQuote(
                courier: 'manual',
                serviceCode: 'flat',
                serviceName: 'Standard shipping',
                cost: DeliverySettings::flatRate(),
            ),
        ];
    }

    public function book(Order $order, RateChoice $choice): Shipment
    {
        return Shipment::create([
            'order_id' => $order->id,
            'provider' => ShippingProvider::Manual,
            'courier' => $choice->courier,
            'service_code' => $choice->serviceCode,
            'tracking_number' => $choice->trackingNumber,
            'status' => ShipmentStatus::Booked,
            'cost' => $choice->cost,
            'currency' => $choice->currency,
            'destination' => $order->shipping_address,
            'booked_at' => now(),
        ]);
    }

    public function schedulePickup(Shipment $shipment, ?string $window = null): void
    {
        // No-op: offline fulfilment has no courier pickup API.
    }

    /** @return list<TrackingUpdate> */
    public function track(Shipment $shipment): array
    {
        return [];
    }

    public function handleWebhook(Request $request): TrackingEvent
    {
        throw new \LogicException('The manual delivery provider has no webhook; status is advanced by an admin.');
    }
}
