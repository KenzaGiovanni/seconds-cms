<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('simple');       // simple | variable
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('draft');       // draft | published
            $table->text('description')->nullable();          // short/plain summary
            $table->json('blocks')->nullable();               // rich description (block system)
            $table->string('sku')->nullable();                // simple product sku
            $table->unsignedBigInteger('price')->nullable();  // simple product price, minor units
            $table->char('currency', 3)->default('IDR');
            $table->integer('stock')->nullable();             // simple product stock
            $table->string('stock_policy')->default('deny');  // none | deny | backorder
            $table->foreignId('featured_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
