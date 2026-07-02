<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/**
 * One-time pull of the official Kemendagri/BPS Indonesia region hierarchy
 * (province -> regency/kabupaten -> district/kecamatan -> village/kelurahan)
 * from ibnux/data-indonesia's single-file mysqldump into our own
 * `id_provinces`/`id_regencies`/`id_districts`/`id_villages` tables, powering
 * the cascading address picker (checkout destination + delivery-settings
 * origin). Not run automatically - a deliberate setup step, safe to re-run
 * (idempotent: truncates + reloads, except `id_districts` which upserts so a
 * KiriminAja `kiriminaja_subdistrict_id` backfill is never wiped by a re-import).
 *
 * Loads the dump into scratch tables via MySQL's own SQL parser (DB::unprepared)
 * rather than hand-parsing the dump - the source data has escaped apostrophes in
 * region names (e.g. "Ma\'u"), which a naive regex would mishandle.
 */
class ImportIndonesiaRegions extends Command
{
    protected $signature = 'regions:import-indonesia {--source= : Local file path to a SQL dump instead of fetching the configured URL (for tests)}';

    protected $description = 'One-time import of the Indonesia province/regency/district/village hierarchy for the address picker';

    public function handle(): int
    {
        $sql = $this->loadDump();

        $this->info('Loading dump into scratch tables...');
        DB::unprepared($sql);

        try {
            $this->transferProvinces();
            $this->transferRegencies();
            $this->transferDistricts();
            $this->transferVillages();
        } finally {
            DB::unprepared('DROP TABLE IF EXISTS t_provinsi, t_kota, t_kecamatan, t_kelurahan');
        }

        $this->info(sprintf(
            'Imported %d provinces, %d regencies, %d districts, %d villages.',
            DB::table('id_provinces')->count(),
            DB::table('id_regencies')->count(),
            DB::table('id_districts')->count(),
            DB::table('id_villages')->count(),
        ));

        return self::SUCCESS;
    }

    private function loadDump(): string
    {
        if ($path = $this->option('source')) {
            return file_get_contents($path);
        }

        $this->info('Fetching region dataset from '.config('seconds.indonesia_regions_source_url').' ...');

        return Http::timeout(120)->get(config('seconds.indonesia_regions_source_url'))->throw()->body();
    }

    private function transferProvinces(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('id_provinces')->truncate();
        Schema::enableForeignKeyConstraints();

        DB::table('t_provinsi')->orderBy('id')->select(['id', 'nama'])->chunk(500, function ($rows) {
            DB::table('id_provinces')->insert($rows->map(fn ($r) => [
                'code' => $r->id, 'name' => $r->nama,
            ])->all());
        });
    }

    private function transferRegencies(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('id_regencies')->truncate();
        Schema::enableForeignKeyConstraints();

        DB::table('t_kota')->orderBy('id')->select(['id', 'nama'])->chunk(500, function ($rows) {
            DB::table('id_regencies')->insert($rows->map(fn ($r) => [
                'code' => $r->id, 'province_code' => substr($r->id, 0, 2), 'name' => $r->nama,
            ])->all());
        });
    }

    /** Upserts (rather than truncate+insert) so a KiriminAja backfill survives a re-import. */
    private function transferDistricts(): void
    {
        DB::table('t_kecamatan')->orderBy('id')->select(['id', 'nama'])->chunk(500, function ($rows) {
            DB::table('id_districts')->upsert(
                $rows->map(fn ($r) => [
                    'code' => $r->id, 'regency_code' => substr($r->id, 0, 4), 'name' => $r->nama,
                ])->all(),
                ['code'],
                ['regency_code', 'name'],
            );
        });
    }

    private function transferVillages(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('id_villages')->truncate();
        Schema::enableForeignKeyConstraints();

        DB::table('t_kelurahan')->orderBy('id')->select(['id', 'nama', 'latitude', 'longitude'])->chunk(1000, function ($rows) {
            DB::table('id_villages')->insert($rows->map(fn ($r) => [
                'code' => $r->id, 'district_code' => substr($r->id, 0, 6), 'name' => $r->nama,
                'latitude' => $r->latitude, 'longitude' => $r->longitude,
            ])->all());
        });
    }
}
