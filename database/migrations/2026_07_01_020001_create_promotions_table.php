<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');                              // PromotionType: automatic | coupon
            $table->string('discount_type');                     // DiscountType: percentage | fixed
            $table->unsignedBigInteger('discount_value');        // percent (1-100) OR fixed minor units per unit
            $table->char('currency', 3)->default('IDR');
            $table->boolean('active')->default(true);

            // Per-order item rules.
            $table->unsignedInteger('min_items')->nullable();            // min cart qty to qualify
            $table->unsignedInteger('max_discounted_items')->nullable(); // cap on discounted units per order

            // Global quota (counted in discounted units).
            $table->unsignedInteger('usage_quota')->nullable();
            $table->unsignedInteger('usage_count')->default(0);

            // Schedule.
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->json('days_of_week')->nullable();   // [0..6], 0=Sunday; null = every day
            $table->time('time_start')->nullable();     // daily window start; null = all day
            $table->time('time_end')->nullable();

            $table->timestamps();

            $table->index(['type', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
