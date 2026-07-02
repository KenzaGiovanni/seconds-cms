<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider');                      // ShippingProvider: manual | kiriminaja
            $table->string('courier')->nullable();           // e.g. jne, sicepat
            $table->string('service_code')->nullable();      // courier service, e.g. reg, yes
            $table->string('external_id')->nullable();       // KiriminAja booking/order id; null for manual
            $table->string('tracking_number')->nullable();   // airway bill / resi
            $table->string('status')->default('pending');    // ShipmentStatus
            $table->unsignedBigInteger('cost')->default(0);  // integer minor units
            $table->char('currency', 3)->default('IDR');
            $table->json('destination')->nullable();         // snapshot of the shipping address at booking
            $table->json('raw_payload')->nullable();         // latest provider payload for the timeline/audit

            // Key transition timestamps (spec §4.0).
            $table->timestamp('booked_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'status']);
            // Dedupe webhook-driven shipments. MySQL treats NULLs as distinct, so
            // manual shipments (external_id = null) are never collapsed together.
            $table->unique(['provider', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
