<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sweep overdue unpaid orders back to cancelled + restock (payment window).
Schedule::command('payments:expire')->everyMinute()->withoutOverlapping();
