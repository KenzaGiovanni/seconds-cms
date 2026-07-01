<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            // Snapshots: keep the reference but never let a deleted product mutate history.
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('name');                            // snapshot at purchase
            $table->string('sku')->nullable();                 // snapshot
            $table->unsignedBigInteger('unit_price');          // snapshot, minor units
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('line_total');          // unit_price * quantity
            $table->char('currency', 3)->default('IDR');
            $table->json('options')->nullable();               // variant options snapshot
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
