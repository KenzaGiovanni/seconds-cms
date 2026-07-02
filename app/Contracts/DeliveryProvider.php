<?php

namespace App\Contracts;

use App\Delivery\Address;
use App\Delivery\Parcel;
use App\Delivery\RateChoice;
use App\Delivery\RateQuote;
use App\Delivery\TrackingEvent;
use App\Delivery\TrackingUpdate;
use App\Enums\ShippingProvider;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Http\Request;

/**
 * The one contract every delivery mode implements (ManualDeliveryProvider,
 * KiriminAjaProvider). Returns value objects, never raw SDK types, so checkout,
 * the shipment state machine, and the order fulfilment flow stay
 * provider-agnostic. Locked in the 4.0 Opus spike - keep it stable.
 *
 * Mirrors the payment side's PaymentGateway contract deliberately.
 */
interface DeliveryProvider
{
    public function provider(): ShippingProvider;

    /**
     * True if the courier/tracking number must be entered by an admin (manual
     * offline fulfilment) rather than returned by the provider on book().
     */
    public function requiresManualTracking(): bool;

    /**
     * Live rate options from origin to destination for a parcel.
     *
     * @return list<RateQuote>
     */
    public function rates(Address $origin, Address $destination, Parcel $parcel): array;

    /**
     * Book the chosen courier for an order and persist a `shipments` row. Does
     * NOT itself advance the order - fulfilment moves only as tracking events
     * arrive (via ShipmentService).
     */
    public function book(Order $order, RateChoice $choice): Shipment;

    /** Schedule a pickup window for an already-booked shipment. No-op for manual. */
    public function schedulePickup(Shipment $shipment, ?string $window = null): void;

    /**
     * Current tracking history for a shipment (read-only detail).
     *
     * @return list<TrackingUpdate>
     */
    public function track(Shipment $shipment): array;

    /**
     * Parse + verify an inbound tracking webhook into a normalised event. Throws
     * if unverified/unparseable. Manual has no webhook and throws.
     */
    public function handleWebhook(Request $request): TrackingEvent;
}
