<?php

namespace App\Enums;

/**
 * A single payment attempt's lifecycle.
 *  - Pending   : created, awaiting the customer's action (or a gateway result).
 *  - Submitted : manual only - customer uploaded proof; awaiting admin review.
 *                Reaching this "stops the clock" so the order won't auto-expire.
 *  - Paid      : settled (Xendit webhook, or admin-confirmed manual transfer).
 *  - Expired   : the payment window elapsed with no customer action.
 *  - Failed    : gateway reported a failure.
 *  - Refunded  : money returned after a successful payment.
 *
 * State moves are monotonic and enforced in PaymentService: once Paid, a payment
 * never returns to Pending; an already-applied event is a safe no-op.
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Submitted = 'submitted';
    case Paid = 'paid';
    case Expired = 'expired';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Submitted => 'Proof submitted',
            self::Paid => 'Paid',
            self::Expired => 'Expired',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
        };
    }

    /** Money has settled (or later been refunded from a settled state). */
    public function isSettled(): bool
    {
        return in_array($this, [self::Paid, self::Refunded], true);
    }

    /** Still awaiting a result - eligible to be marked paid/expired. */
    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Submitted], true);
    }

    /**
     * The customer has completed their side (uploaded proof or fully paid), so
     * the auto-expiry clock no longer applies to this payment.
     */
    public function stopsExpiryClock(): bool
    {
        return in_array($this, [self::Submitted, self::Paid, self::Refunded], true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
