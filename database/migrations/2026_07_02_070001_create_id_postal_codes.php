<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Postal codes at the urban/kelurahan level - a separate dataset from
 * id_provinces/id_regencies/id_districts/id_villages (§21.1) because the
 * source file (Kenza-provided, mysql_provinces.sql) has no stable numeric
 * codes for city/sub_district, only free-text names. Raw text is preserved
 * as-imported; `district_code` is a best-effort match against id_districts
 * (nullable - not every row can be matched unambiguously, see
 * regions:import-postal-codes).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('id_postal_codes', function (Blueprint $table) {
            $table->id();
            $table->string('urban', 100);       // kelurahan/desa, raw text
            $table->string('sub_district', 100); // kecamatan, raw text
            $table->string('city', 100);          // kabupaten/kota, raw text (no Kota/Kabupaten prefix in source)
            $table->string('province_code', 2);
            $table->string('postal_code', 5);
            $table->string('district_code', 6)->nullable(); // best-effort match to id_districts.code

            $table->foreign('province_code')->references('code')->on('id_provinces')->cascadeOnDelete();
            $table->foreign('district_code')->references('code')->on('id_districts')->nullOnDelete();
            $table->index('postal_code');
            $table->index('district_code');
            $table->index(['province_code', 'sub_district']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('id_postal_codes');
    }
};
