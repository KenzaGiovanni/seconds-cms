<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every outbound API call to Xendit/KiriminAja, and every inbound webhook
 * from them, for debugging. Deliberately unopinionated about what counts as
 * "worth logging" - every call is logged, success or failure (Kenza, 07-02:
 * "I need to see every log on that API"). Append-only, no updated_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider');                  // xendit | kiriminaja
            $table->string('direction');                  // outbound | inbound
            $table->string('method')->nullable();          // GET/POST/etc, or null when not applicable
            $table->string('endpoint');                     // URL or SDK method name
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->boolean('successful')->default(false);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error_message')->nullable();
            // Optional link to the order/payment/shipment this call was for.
            $table->nullableMorphs('loggable');
            $table->timestamp('created_at')->nullable();

            $table->index(['provider', 'created_at']);
            $table->index('successful');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
