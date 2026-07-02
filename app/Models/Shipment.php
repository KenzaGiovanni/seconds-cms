<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use App\Enums\ShippingProvider;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One delivery attempt for an order. An order normally has a single active
 * shipment, but a cancelled/returned one can be followed by a re-book, so this
 * is a HasMany and the order's fulfilment state derives from the latest active
 * shipment. See ShipmentService for the state machine.
 */
class Shipment extends Model
{
    protected $fillable = [
        'order_id', 'provider', 'courier', 'service_code', 'external_id',
        'tracking_number', 'status', 'cost', 'currency', 'destination',
        'raw_payload', 'booked_at', 'picked_up_at', 'delivered_at', 'cancelled_at',
    ];

    protected $attributes = [
        'currency' => Money::DEFAULT_CURRENCY,
        'status' => 'pending',
        'cost' => 0,
    ];

    protected $casts = [
        'provider' => ShippingProvider::class,
        'status' => ShipmentStatus::class,
        'cost' => 'integer',
        'destination' => 'array',
        'raw_payload' => 'array',
        'booked_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** True while the shipment is still live (blocks a duplicate booking). */
    public function isActive(): bool
    {
        return ! in_array($this->status, [
            ShipmentStatus::Cancelled,
            ShipmentStatus::Returned,
        ], true);
    }

    public function formattedCost(): string
    {
        return Money::format((int) $this->cost, $this->currency ?? Money::DEFAULT_CURRENCY);
    }
}
