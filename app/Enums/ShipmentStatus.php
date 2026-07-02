<?php

namespace App\Enums;

/**
 * The shipment lifecycle state machine (locked 07-02, Phase 4.0 spike).
 *
 * Progression: pending -> booked -> picked_up -> in_transit -> delivered,
 * with cancelled / returned as exits. Tracking webhooks arrive at-least-once
 * and out of order, so ShipmentService advances a shipment only FORWARD by
 * rank() (a lower/equal-ranked event is a safe no-op) - the same monotonic
 * discipline the payment state machine uses. This enum is the single source of
 * truth for legal moves and for how a shipment status reconciles with the
 * order fulfilment state (see orderFulfilmentTarget()).
 *
 * Order mapping (spec §4.0): paid -> (book) -> fulfilled once picked_up/in_transit
 * -> completed on delivered. Booking alone does NOT advance the order.
 */
enum ShipmentStatus: string
{
    case Pending = 'pending';
    case Booked = 'booked';
    case PickedUp = 'picked_up';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Booked => 'Booked',
            self::PickedUp => 'Picked up',
            self::InTransit => 'In transit',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
            self::Returned => 'Returned',
        };
    }

    /**
     * States this state may legally move to.
     *
     * @return list<self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Pending => [self::Booked, self::Cancelled],
            self::Booked => [self::PickedUp, self::Cancelled],
            self::PickedUp => [self::InTransit, self::Delivered, self::Returned],
            self::InTransit => [self::Delivered, self::Returned],
            self::Delivered, self::Cancelled, self::Returned => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->transitions(), true);
    }

    /** Delivered/cancelled/returned are final - nothing moves out of them. */
    public function isTerminal(): bool
    {
        return $this->transitions() === [];
    }

    /** Cancelled + returned are "exit" outcomes reachable from any active state. */
    public function isExit(): bool
    {
        return in_array($this, [self::Cancelled, self::Returned], true);
    }

    /**
     * Position along the happy path, used to reject out-of-order / duplicate
     * tracking events (a lower-or-equal rank never overwrites a higher one).
     * Exit statuses are handled separately and return -1.
     */
    public function rank(): int
    {
        return match ($this) {
            self::Pending => 0,
            self::Booked => 1,
            self::PickedUp => 2,
            self::InTransit => 3,
            self::Delivered => 4,
            self::Cancelled, self::Returned => -1,
        };
    }

    /**
     * The order fulfilment status this shipment status should drive the order
     * toward (reusing the Phase 2 order state machine - never forked). Null =
     * leave the order where it is.
     */
    public function orderFulfilmentTarget(): ?OrderStatus
    {
        return match ($this) {
            self::PickedUp, self::InTransit => OrderStatus::Fulfilled,
            self::Delivered => OrderStatus::Completed,
            default => null,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
