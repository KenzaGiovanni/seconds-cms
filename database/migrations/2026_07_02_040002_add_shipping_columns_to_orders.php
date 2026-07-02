<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Snapshot of the rate chosen at checkout (price locked at purchase,
            // like line items) - shipping_total already existed but was unused.
            $table->string('shipping_courier')->nullable()->after('shipping_total');
            $table->string('shipping_service_code')->nullable()->after('shipping_courier');
            $table->string('shipping_service_name')->nullable()->after('shipping_service_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_courier', 'shipping_service_code', 'shipping_service_name']);
        });
    }
};
