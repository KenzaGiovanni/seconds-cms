<?php

namespace App\Delivery;

use App\Enums\ShipmentStatus;
use Illuminate\Support\Carbon;

/**
 * One entry in a shipment's tracking history, returned by
 * DeliveryProvider::track(). Read-only detail for the customer/admin timeline;
 * it does not itself drive state (that is the webhook path via TrackingEvent).
 */
class TrackingUpdate
{
    public function __construct(
        public readonly ShipmentStatus $status,
        public readonly string $description,
        public readonly ?Carbon $occurredAt = null,
    ) {}
}
