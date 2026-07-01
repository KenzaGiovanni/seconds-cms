<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Deadline for the customer to complete payment; stamped on entering
            // awaiting_payment. Past this, the payments:expire sweep cancels + restocks.
            $table->timestamp('payment_due_at')->nullable()->after('cancelled_at');
            // Why an order was cancelled (e.g. payment_expired) - shown in admin.
            $table->string('cancellation_reason')->nullable()->after('payment_due_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_due_at', 'cancellation_reason']);
        });
    }
};
