<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();            // page | post (STI discriminator)
            $table->string('title');
            $table->string('slug')->unique();           // v1: flat, globally-unique single-segment URL space
            $table->string('status')->default('draft'); // draft | published | scheduled
            $table->longText('body')->nullable();
            $table->json('blocks')->nullable();         // ordered [{ type, data }]
            $table->text('excerpt')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
