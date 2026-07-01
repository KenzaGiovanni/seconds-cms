<?php

namespace App\Console\Commands;

use App\Payments\PaymentService;
use Illuminate\Console\Command;

/**
 * Cancels orders whose payment window has elapsed without the customer acting,
 * returning their reserved inventory. Scheduled every minute (routes/console.php);
 * idempotent, so running it manually or twice is harmless.
 */
class ExpireOverduePayments extends Command
{
    protected $signature = 'payments:expire';

    protected $description = 'Cancel overdue unpaid orders and return their reserved stock';

    public function handle(PaymentService $payments): int
    {
        $count = $payments->expireOverdue();

        $this->info("Expired {$count} overdue order(s).");

        return self::SUCCESS;
    }
}
