<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sweep overdue unpaid orders back to cancelled + restock (payment window).
Schedule::command('payments:expire')->everyMinute()->withoutOverlapping();

// Safety net for missed Xendit webhooks - re-check stale pending payments.
Schedule::command('payments:reconcile')->everyFiveMinutes()->withoutOverlapping();

// Safety net for missed KiriminAja tracking webhooks - re-check stale shipments.
Schedule::command('delivery:reconcile')->everyFiveMinutes()->withoutOverlapping();
