<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway');                       // PaymentProvider: manual | xendit
            $table->string('method');                        // PaymentMethod: bank_transfer | va | qris | ewallet | card
            $table->string('external_id')->nullable();       // Xendit invoice/charge id; null for manual
            $table->string('status')->default('pending');    // PaymentStatus
            $table->unsignedBigInteger('amount');            // integer minor units
            $table->char('currency', 3)->default('IDR');
            $table->json('raw_payload')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Manual bank-transfer / proof-of-payment fields.
            $table->string('proof_path')->nullable();
            $table->timestamp('proof_uploaded_at')->nullable();
            $table->string('payer_reference')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('rejection_reason')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'status']);
            // Dedupe webhook-driven payments. MySQL treats NULLs as distinct, so
            // manual payments (external_id = null) are never collapsed together.
            $table->unique(['gateway', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
