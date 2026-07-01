<?php

namespace App\Console\Commands;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Payments\PaymentService;
use App\Payments\XenditGateway;
use Illuminate\Console\Command;

/**
 * Safety net for missed Xendit webhooks: re-queries stale pending Xendit
 * payments and applies whatever Xendit reports through the same idempotent
 * PaymentService::applyEvent() path a webhook would use.
 */
class ReconcileXenditPayments extends Command
{
    protected $signature = 'payments:reconcile';

    protected $description = 'Re-check stale pending Xendit payments against Xendit and apply any settled result';

    public function handle(XenditGateway $gateway, PaymentService $payments): int
    {
        $stale = Payment::query()
            ->where('gateway', PaymentProvider::Xendit->value)
            ->where('status', PaymentStatus::Pending->value)
            ->whereNotNull('external_id')
            ->where('created_at', '<', now()->subMinutes(5))
            ->get();

        $count = 0;

        foreach ($stale as $payment) {
            try {
                $payments->applyEvent($gateway->reconcile($payment));
                $count++;
            } catch (\RuntimeException $e) {
                $this->warn("Could not reconcile payment {$payment->id}: {$e->getMessage()}");
            }
        }

        $this->info("Reconciled {$count} stale Xendit payment(s).");

        return self::SUCCESS;
    }
}
