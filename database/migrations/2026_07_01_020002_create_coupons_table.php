<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->unsignedInteger('max_uses')->nullable(); // per-code redemption cap; null = unlimited
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamps();

            $table->index('promotion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
