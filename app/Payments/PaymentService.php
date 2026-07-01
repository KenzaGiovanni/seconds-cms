<?php

namespace App\Payments;

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Support\PaymentSettings;
use Illuminate\Support\Facades\DB;

/**
 * The one place order payment state changes. Every path that can mark an order
 * paid - an admin confirming a manual transfer, a Xendit webhook, a
 * reconciliation re-check - funnels through the same row-locked, monotonic
 * helper here, so "mark paid" behaves identically and idempotently regardless
 * of source. Also owns the shared payment-window expiry sweep.
 *
 * Locked in the 3.0 Opus spike (money = highest blast radius).
 */
class PaymentService
{
    public function provider(): PaymentProvider
    {
        return PaymentSettings::provider();
    }

    /** Resolve the gateway for a provider (defaults to the active one). */
    public function gateway(?PaymentProvider $provider = null): PaymentGateway
    {
        return match ($provider ?? $this->provider()) {
            PaymentProvider::Manual => app(ManualGateway::class),
            // Bound in Phase 3.2 when Xendit activation ships.
            PaymentProvider::Xendit => throw new \RuntimeException('Xendit is not configured yet (Phase 3.2).'),
        };
    }

    /**
     * Start payment for an order: stamp its payment window (once) and create a
     * pending payment via the active gateway. Returns what the storefront shows next.
     */
    public function initiate(Order $order, ?PaymentMethod $method = null): PaymentIntent
    {
        if ($order->payment_due_at === null) {
            $order->payment_due_at = PaymentSettings::dueAt();
            $order->save();
        }

        $gateway = $this->gateway();

        return $gateway->createPayment($order, $method ?? $gateway->supportedMethods()[0]);
    }

    /** Manual: customer uploaded proof. Moves pending -> submitted (stops the expiry clock). */
    public function submitProof(Payment $payment, string $proofPath, ?string $payerReference = null): void
    {
        DB::transaction(function () use ($payment, $proofPath, $payerReference) {
            $fresh = Payment::whereKey($payment->id)->lockForUpdate()->first();

            if ($fresh->status !== PaymentStatus::Pending) {
                return; // already submitted/paid - ignore
            }

            $fresh->status = PaymentStatus::Submitted;
            $fresh->proof_path = $proofPath;
            $fresh->proof_uploaded_at = now();
            $fresh->payer_reference = $payerReference;
            $fresh->rejection_reason = null;
            $fresh->save();
        });

        $payment->refresh();
    }

    /** Admin confirms a manual transfer: records who verified it, then marks paid. */
    public function confirmManual(Payment $payment, User $admin): void
    {
        DB::transaction(function () use ($payment, $admin) {
            $fresh = Payment::whereKey($payment->id)->lockForUpdate()->first();
            $order = Order::whereKey($fresh->order_id)->lockForUpdate()->first();

            if ($fresh->status !== PaymentStatus::Paid) {
                $fresh->verified_by = $admin->id;
                $fresh->verified_at = now();
            }

            $this->applyPaid($fresh, $order);
        });

        $payment->refresh();
    }

    /** Admin rejects submitted proof: back to pending so the customer can re-upload. */
    public function rejectManual(Payment $payment, string $reason): void
    {
        DB::transaction(function () use ($payment, $reason) {
            $fresh = Payment::whereKey($payment->id)->lockForUpdate()->first();

            if ($fresh->status === PaymentStatus::Submitted) {
                $fresh->status = PaymentStatus::Pending;
                $fresh->rejection_reason = $reason;
                $fresh->save();
            }
        });

        $payment->refresh();
    }

    /**
     * Apply a normalised gateway event (webhook / reconcile). Idempotent + safe
     * against out-of-order delivery: unknown external_id is a no-op, and
     * monotonic guards prevent a late/duplicate event from downgrading state.
     */
    public function applyEvent(PaymentEvent $event): void
    {
        DB::transaction(function () use ($event) {
            $payment = Payment::query()
                ->where('external_id', $event->externalId)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                return; // unknown payment - nothing to apply
            }

            $order = Order::whereKey($payment->order_id)->lockForUpdate()->first();

            // Record the latest raw payload for the timeline/audit.
            $payment->raw_payload = $event->rawPayload;

            match ($event->status) {
                PaymentStatus::Paid => $this->applyPaid($payment, $order),
                PaymentStatus::Expired => $this->applyExpired($payment, $order),
                PaymentStatus::Failed => $this->applyOpenTransition($payment, PaymentStatus::Failed),
                default => $payment->save(), // pending/submitted/refunded events: persist payload only
            };
        });
    }

    /**
     * Cancel overdue, un-acted-on orders and return their inventory. The clock
     * stops once the customer submits proof or pays, so this only ever cancels
     * orders whose payment is still pending at the deadline. Idempotent + row-locked.
     *
     * @return int number of orders expired
     */
    public function expireOverdue(): int
    {
        $ids = Order::query()
            ->where('status', OrderStatus::AwaitingPayment->value)
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<', now())
            ->pluck('id');

        $expired = 0;

        foreach ($ids as $id) {
            DB::transaction(function () use ($id, &$expired) {
                $order = Order::whereKey($id)->lockForUpdate()->first();

                if (! $order
                    || $order->status !== OrderStatus::AwaitingPayment
                    || $order->payment_due_at === null
                    || $order->payment_due_at->isFuture()) {
                    return;
                }

                // Customer already acted (proof submitted, or paid) - never expire.
                $acted = $order->payments()
                    ->whereIn('status', [
                        PaymentStatus::Submitted->value,
                        PaymentStatus::Paid->value,
                        PaymentStatus::Refunded->value,
                    ])
                    ->exists();

                if ($acted) {
                    return;
                }

                $order->payments()
                    ->where('status', PaymentStatus::Pending->value)
                    ->update(['status' => PaymentStatus::Expired->value]);

                $order->cancellation_reason = 'payment_expired';
                $order->transitionTo(OrderStatus::Cancelled); // restocks via the Phase 2 path
                $expired++;
            });
        }

        return $expired;
    }

    /**
     * Settle a payment + move the order to paid, within a held lock. Monotonic:
     * an already-paid payment is a no-op, and the order only advances if it is
     * still awaiting payment (never moved backwards).
     */
    private function applyPaid(Payment $payment, Order $order): void
    {
        if ($payment->status === PaymentStatus::Paid || $payment->status === PaymentStatus::Refunded) {
            $payment->save(); // persist any raw_payload/verify fields; no state change

            return;
        }

        $payment->status = PaymentStatus::Paid;
        $payment->paid_at ??= now();
        $payment->save();

        if ($order->status === OrderStatus::AwaitingPayment) {
            $order->transitionTo(OrderStatus::Paid);
        }
    }

    /** A gateway-reported expiry: mark the payment expired + cancel/restock the order. */
    private function applyExpired(Payment $payment, Order $order): void
    {
        if (! $payment->status->isOpen()) {
            $payment->save();

            return;
        }

        $payment->status = PaymentStatus::Expired;
        $payment->save();

        if ($order->status === OrderStatus::AwaitingPayment && $order->canTransitionTo(OrderStatus::Cancelled)) {
            $order->cancellation_reason = 'payment_expired';
            $order->transitionTo(OrderStatus::Cancelled);
        }
    }

    private function applyOpenTransition(Payment $payment, PaymentStatus $to): void
    {
        if ($payment->status->isOpen()) {
            $payment->status = $to;
        }

        $payment->save();
    }
}
