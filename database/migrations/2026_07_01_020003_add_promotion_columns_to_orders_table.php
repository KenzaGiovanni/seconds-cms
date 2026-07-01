<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // What discount was applied, snapshotted so cancel can release it.
            $table->foreignId('promotion_id')->nullable()->after('discount_total')->constrained('promotions')->nullOnDelete();
            $table->foreignId('coupon_id')->nullable()->after('promotion_id')->constrained('coupons')->nullOnDelete();
            $table->string('coupon_code')->nullable()->after('coupon_id'); // snapshot of the code text
            $table->unsignedInteger('discount_units')->default(0)->after('coupon_code'); // discounted units consumed
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_id');
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn(['coupon_code', 'discount_units']);
        });
    }
};
