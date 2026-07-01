<?php

namespace App\Enums;

/**
 * The order lifecycle state machine (locked 07-01, Phase 2.0 spike).
 *
 * Flow: pending -> awaiting_payment -> paid -> fulfilled -> completed,
 * with cancelled / refunded as exits. Transitions are enforced by
 * Order::transitionTo(); this enum is the single source of truth for what
 * is allowed. "Who may trigger" is layered on at the action/UI layer:
 *  - pending -> awaiting_payment : the system, on checkout place-order.
 *  - awaiting_payment -> paid    : the system, on payment (Phase 3 webhook) or a
 *                                  staff "mark paid" (manual/offline gateway).
 *  - paid -> fulfilled -> completed : staff (order management).
 *  - cancelled / refunded        : staff.
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case AwaitingPayment = 'awaiting_payment';
    case Paid = 'paid';
    case Fulfilled = 'fulfilled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::AwaitingPayment => 'Awaiting payment',
            self::Paid => 'Paid',
            self::Fulfilled => 'Fulfilled',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
        };
    }

    /**
     * States this state may move to.
     *
     * @return list<self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Pending => [self::AwaitingPayment, self::Cancelled],
            self::AwaitingPayment => [self::Paid, self::Cancelled],
            self::Paid => [self::Fulfilled, self::Refunded, self::Cancelled],
            self::Fulfilled => [self::Completed, self::Refunded],
            self::Completed => [self::Refunded],
            self::Cancelled, self::Refunded => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->transitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this->transitions() === [];
    }

    /** Cancelling before payment/fulfilment should return reserved stock. */
    public function shouldRestockOnCancel(): bool
    {
        return in_array($this, [self::AwaitingPayment, self::Paid], true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
