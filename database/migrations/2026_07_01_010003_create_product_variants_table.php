<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->unsignedBigInteger('price')->default(0);  // minor units
            $table->char('currency', 3)->default('IDR');
            $table->integer('stock')->default(0);
            $table->json('options')->nullable();              // {"size":"L","color":"red"}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
