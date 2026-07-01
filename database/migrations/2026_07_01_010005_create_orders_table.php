<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();               // human-friendly reference
            $table->string('status')->default('pending');     // OrderStatus
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // null = guest
            $table->string('email');
            $table->string('customer_name')->nullable();
            $table->string('phone')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('shipping_total')->default(0);
            $table->unsignedBigInteger('discount_total')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->char('currency', 3)->default('IDR');
            $table->text('notes')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
