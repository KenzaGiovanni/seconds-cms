<?php

namespace App\Delivery;

use App\Contracts\DeliveryProvider;
use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Enums\ShippingProvider;
use App\Models\Order;
use App\Models\Shipment;
use App\Support\DeliverySettings;
use Illuminate\Support\Facades\DB;

/**
 * The one place shipment + order-fulfilment state changes. Every path that can
 * advance a shipment - a tracking webhook, a reconcile re-check, an admin
 * marking a manual shipment picked-up/delivered - funnels through the same
 * row-locked, monotonic helper here, so status behaves identically and
 * idempotently regardless of source, and the order fulfilment state is kept in
 * lock-step with it (reusing the Phase 2 order state machine, never forked).
 *
 * Locked in the 4.0 Opus spike - direct analog of PaymentService.
 */
class ShipmentService
{
    public function provider(): ShippingProvider
    {
        return DeliverySettings::provider();
    }

    /** Resolve the delivery provider (defaults to the active one). */
    public function driver(?ShippingProvider $provider = null): DeliveryProvider
    {
        return match ($provider ?? $this->provider()) {
            ShippingProvider::Manual => app(ManualDeliveryProvider::class),
            ShippingProvider::Kiriminaja => app(KiriminAjaProvider::class),
        };
    }

    /**
     * Rate options for an order's destination via the active provider. Falls
     * back to a flat-rate quote if the provider call yields nothing or throws,
     * so checkout never hard-blocks (spec §4.1 graceful degradation).
     *
     * @return list<RateQuote>
     */
    public function rates(Order $order): array
    {
        return $this->ratesFor($this->destinationFor($order), (int) $order->subtotal);
    }

    /**
     * Rate options for a destination address before an order exists (checkout,
     * ahead of place-order). Same graceful degradation as rates(Order).
     *
     * @return list<RateQuote>
     */
    public function previewRates(Address $destination, int $itemValue = 0): array
    {
        return $this->ratesFor($destination, $itemValue);
    }

    /** @return list<RateQuote> */
    private function ratesFor(Address $destination, int $itemValue): array
    {
        $parcel = DeliverySettings::defaultParcel($itemValue);

        try {
            $rates = $this->driver()->rates(DeliverySettings::origin(), $destination, $parcel);

            if ($rates !== []) {
                return $rates;
            }
        } catch (\Throwable $e) {
            // Fall through to the flat-rate fallback below.
        }

        return [new RateQuote(
            courier: 'manual',
            serviceCode: 'flat',
            serviceName: 'Standard shipping',
            cost: DeliverySettings::flatRate(),
        )];
    }

    /**
     * Book the chosen courier for an order. Guarded against double-booking: an
     * order that already has an active (non-cancelled/returned) shipment returns
     * that one instead of creating a second. Booking does not move the order -
     * fulfilment advances only as tracking events arrive.
     */
    public function book(Order $order, RateChoice $choice, ?ShippingProvider $provider = null): Shipment
    {
        return DB::transaction(function () use ($order, $choice, $provider) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();

            $existing = $locked->shipments()
                ->whereNotIn('status', [ShipmentStatus::Cancelled->value, ShipmentStatus::Returned->value])
                ->latest('id')
                ->first();

            if ($existing) {
                return $existing; // already booked - no duplicate
            }

            return $this->driver($provider)->book($locked, $choice);
        });
    }

    /**
     * Apply a normalised tracking event (webhook / reconcile). Idempotent + safe
     * against out-of-order delivery: unknown external_id is a no-op, and the
     * monotonic guard (see advance()) drops any event that would not move the
     * shipment forward.
     */
    public function applyTrackingEvent(TrackingEvent $event): void
    {
        DB::transaction(function () use ($event) {
            $shipment = Shipment::query()
                ->where('external_id', $event->externalId)
                ->lockForUpdate()
                ->first();

            if (! $shipment) {
                return; // unknown shipment - nothing to apply
            }

            $order = Order::whereKey($shipment->order_id)->lockForUpdate()->first();

            // Record the latest raw payload for the timeline/audit regardless.
            $shipment->raw_payload = $event->rawPayload;

            $this->advance($shipment, $order, $event->status);
        });
    }

    /**
     * Admin advances a manual shipment (mark picked-up / in-transit / delivered
     * / returned / cancelled). Same locked, monotonic path as the webhook, so a
     * manual and an automated advance behave identically. Returns whether the
     * status actually changed.
     */
    public function advanceManual(Shipment $shipment, ShipmentStatus $to): bool
    {
        $changed = false;

        DB::transaction(function () use ($shipment, $to, &$changed) {
            $fresh = Shipment::whereKey($shipment->id)->lockForUpdate()->first();
            $order = Order::whereKey($fresh->order_id)->lockForUpdate()->first();

            $changed = $this->advance($fresh, $order, $to);
        });

        $shipment->refresh();

        return $changed;
    }

    /**
     * The single locked, monotonic transition. Advances a shipment forward by
     * rank (a lower-or-equal-ranked status is a no-op), allows an exit
     * (cancelled/returned) from any active state, refuses to move out of a final
     * state, stamps the matching timestamp, then reconciles the order fulfilment
     * state. Must run inside a transaction with both rows locked.
     *
     * @return bool whether the shipment status changed
     */
    private function advance(Shipment $shipment, Order $order, ShipmentStatus $to): bool
    {
        $current = $shipment->status;

        // Already final - never change (guards duplicate/late "delivered" etc.).
        if ($current === ShipmentStatus::Delivered || $current->isExit()) {
            $shipment->save(); // persist any raw_payload update; no state change

            return false;
        }

        if ($to->isExit()) {
            // Cancelled/returned may arrive from any active state.
            $shipment->status = $to;
            $shipment->cancelled_at = now();
            $shipment->save();

            return true;
        }

        // Progressive status: only ever move forward.
        if ($to->rank() <= $current->rank()) {
            $shipment->save();

            return false;
        }

        $shipment->status = $to;
        $this->stamp($shipment, $to);
        $shipment->save();

        $this->syncOrderFulfilment($order, $to);

        return true;
    }

    /** Stamp the timestamp column matching a progressive status. */
    private function stamp(Shipment $shipment, ShipmentStatus $to): void
    {
        $column = match ($to) {
            ShipmentStatus::Booked => 'booked_at',
            ShipmentStatus::PickedUp => 'picked_up_at',
            ShipmentStatus::Delivered => 'delivered_at',
            default => null,
        };

        if ($column && $shipment->{$column} === null) {
            $shipment->{$column} = now();
        }
    }

    /**
     * Reconcile the order fulfilment state with a shipment status, walking the
     * order forward through the legal chain (paid -> fulfilled -> completed) up
     * to the mapped target. Never moves the order backwards and never touches a
     * cancelled/refunded order (canTransitionTo() guards both).
     */
    private function syncOrderFulfilment(Order $order, ShipmentStatus $to): void
    {
        $target = $to->orderFulfilmentTarget();

        if ($target === null) {
            return;
        }

        $steps = $target === OrderStatus::Completed
            ? [OrderStatus::Fulfilled, OrderStatus::Completed]
            : [OrderStatus::Fulfilled];

        foreach ($steps as $step) {
            if ($order->canTransitionTo($step)) {
                $order->transitionTo($step);
            }
        }
    }

    private function destinationFor(Order $order): Address
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
}
