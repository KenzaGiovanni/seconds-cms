<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reference geography for Indonesian addresses (province -> regency/kabupaten
 * -> district/kecamatan -> village/kelurahan). Populated one-time by
 * `regions:import-indonesia` from the official Kemendagri/BPS hierarchical
 * code dataset (ibnux/data-indonesia) - not seeded on every install, not
 * tied to any single feature. Codes are the natural key (a child's code is
 * always prefixed by its parent's), so they're stored as the primary key
 * rather than a synthetic auto-increment id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('id_provinces', function (Blueprint $table) {
            $table->string('code', 2)->primary();
            $table->string('name', 64);
        });

        Schema::create('id_regencies', function (Blueprint $table) {
            $table->string('code', 4)->primary();
            $table->string('province_code', 2);
            $table->string('name', 64);
            $table->foreign('province_code')->references('code')->on('id_provinces')->cascadeOnDelete();
            $table->index('province_code');
        });

        Schema::create('id_districts', function (Blueprint $table) {
            $table->string('code', 6)->primary();
            $table->string('regency_code', 4);
            $table->string('name', 64);
            // Backfilled one-time via a KiriminAja name-reconciliation pass once
            // real API credentials exist (§19 follow-up) - null until then, at
            // which point live per-courier rates activate for that district with
            // zero further code changes (Address::subdistrictId reads this).
            $table->unsignedInteger('kiriminaja_subdistrict_id')->nullable()->index();
            $table->foreign('regency_code')->references('code')->on('id_regencies')->cascadeOnDelete();
            $table->index('regency_code');
        });

        Schema::create('id_villages', function (Blueprint $table) {
            $table->string('code', 10)->primary();
            $table->string('district_code', 6);
            $table->string('name', 64);
            $table->decimal('latitude', 12, 10)->nullable();
            $table->decimal('longitude', 13, 10)->nullable();
            $table->foreign('district_code')->references('code')->on('id_districts')->cascadeOnDelete();
            $table->index('district_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('id_villages');
        Schema::dropIfExists('id_districts');
        Schema::dropIfExists('id_regencies');
        Schema::dropIfExists('id_provinces');
    }
};
