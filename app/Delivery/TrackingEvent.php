<?php

namespace App\Delivery;

use App\Enums\ShipmentStatus;

/**
 * A normalised shipment event parsed from a provider tracking webhook. Together
 * with (provider, external_id) the signature dedupes at-least-once, possibly
 * out-of-order deliveries - see ShipmentService::applyTrackingEvent(). Direct
 * analog of the payment side's PaymentEvent.
 */
class TrackingEvent
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public readonly string $externalId,
        public readonly ShipmentStatus $status,
        public readonly string $signature,
        public readonly array $rawPayload = [],
    ) {}
}
