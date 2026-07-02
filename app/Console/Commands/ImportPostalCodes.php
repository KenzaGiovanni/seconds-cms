<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * One-time import of urban/kelurahan-level postal codes (Kenza-provided
 * mysql_provinces.sql: `db_province_data` + `db_postal_code_data`). Separate
 * from `regions:import-indonesia` (§21.1) because this source has no stable
 * numeric codes for city/sub_district - only free-text names - so rows are
 * imported raw, then `district_code` is backfilled by a best-effort name
 * match against `id_districts` (already imported from the official
 * Kemendagri/BPS dataset). Left null where the match is ambiguous or absent -
 * never guessed, always reported.
 *
 * Matching runs in three increasingly loose passes, each only touching rows
 * the previous pass left unmatched:
 *  1. Exact name match, unique within the province.
 *  2. "Loose" match - strips a trailing `(alt name)` remark and all
 *     spaces/hyphens/dots before comparing (the two datasets disagree on
 *     spacing for some districts, e.g. "Blangkejeren" vs "BLANG KEJEREN",
 *     and this source appends alt-spellings in parens, e.g.
 *     "SEUNUDDON (SEUNUDON)") - still only applied where unique.
 *  3. Either pass's name match, disambiguated by the city/regency name
 *     (stripped of "Kabupaten "/"Kota ") for the ~1-2% of district names
 *     that repeat within a province.
 */
class ImportPostalCodes extends Command
{
    protected $signature = 'regions:import-postal-codes {--source= : Local file path to the mysqldump}';

    protected $description = 'One-time import of postal codes at the urban/kelurahan level, matched to the existing district hierarchy';

    public function handle(): int
    {
        $path = $this->option('source');

        if (! $path || ! is_file($path)) {
            $this->error('Pass --source=<path to mysql_provinces.sql>. This dataset has no public URL - it must be supplied as a local file.');

            return self::FAILURE;
        }

        $this->info('Loading dump into scratch tables...');
        DB::unprepared(file_get_contents($path));

        try {
            $this->transferPostalCodes();
        } finally {
            DB::unprepared('DROP TABLE IF EXISTS db_province_data, db_postal_code_data');
        }

        $this->matchExact();
        $this->matchLoose();
        $this->matchByCityDisambiguation();

        $total = DB::table('id_postal_codes')->count();
        $unmatched = $total - DB::table('id_postal_codes')->whereNotNull('district_code')->count();

        $this->info(sprintf('Imported %d postal codes. Matched to a district: %d. Unmatched: %d (%.1f%%).',
            $total, $total - $unmatched, $unmatched, $total > 0 ? $unmatched / $total * 100 : 0));

        if ($unmatched > 0) {
            $sample = DB::table('id_postal_codes')->whereNull('district_code')
                ->select('sub_district', 'city', 'province_code')->distinct()->limit(10)->get();
            $this->warn('Sample unmatched sub-districts (name drift between the two datasets, or a genuine gap):');
            foreach ($sample as $row) {
                $this->line("  - {$row->sub_district}, {$row->city} (province {$row->province_code})");
            }
        }

        return self::SUCCESS;
    }

    private function transferPostalCodes(): void
    {
        DB::table('id_postal_codes')->truncate();

        DB::table('db_postal_code_data')->orderBy('id')
            ->select(['urban', 'sub_district', 'city', 'province_code', 'postal_code'])
            ->chunk(1000, function ($rows) {
                DB::table('id_postal_codes')->insert($rows->map(fn ($r) => [
                    'urban' => $r->urban,
                    'sub_district' => $r->sub_district,
                    'city' => $r->city,
                    'province_code' => str_pad((string) $r->province_code, 2, '0', STR_PAD_LEFT),
                    'postal_code' => $r->postal_code,
                ])->all());
            });
    }

    /** Pass 1: exact (trimmed, uppercased) name match, unique within the province. */
    private function matchExact(): void
    {
        DB::statement(<<<'SQL'
            UPDATE id_postal_codes pc
            JOIN (
                SELECT MIN(d.code) AS district_code, r.province_code, UPPER(TRIM(d.name)) AS dname
                FROM id_districts d
                JOIN id_regencies r ON r.code = d.regency_code
                GROUP BY r.province_code, UPPER(TRIM(d.name))
                HAVING COUNT(*) = 1
            ) matched ON matched.province_code = pc.province_code
                AND matched.dname = UPPER(TRIM(pc.sub_district))
            SET pc.district_code = matched.district_code
            WHERE pc.district_code IS NULL
        SQL);
    }

    /** Pass 2: loose match (strips parenthetical remarks + spaces/hyphens/dots), unique within the province. */
    private function matchLoose(): void
    {
        $looseDistrict = "UPPER(REPLACE(REPLACE(REPLACE(d.name, ' ', ''), '-', ''), '.', ''))";
        $loosePostal = "UPPER(REPLACE(REPLACE(REPLACE(TRIM(SUBSTRING_INDEX(pc.sub_district, '(', 1)), ' ', ''), '-', ''), '.', ''))";

        DB::statement(<<<SQL
            UPDATE id_postal_codes pc
            JOIN (
                SELECT MIN(d.code) AS district_code, r.province_code, {$looseDistrict} AS dname
                FROM id_districts d
                JOIN id_regencies r ON r.code = d.regency_code
                GROUP BY r.province_code, {$looseDistrict}
                HAVING COUNT(*) = 1
            ) matched ON matched.province_code = pc.province_code
                AND matched.dname = {$loosePostal}
            SET pc.district_code = matched.district_code
            WHERE pc.district_code IS NULL
        SQL);
    }

    /**
     * Pass 3: for the district names that repeat within a province (so passes
     * 1-2 skipped them as ambiguous), disambiguate using the city/regency
     * name - stripped of its "Kabupaten "/"Kota " prefix, since the source
     * file's `city` column never includes it.
     */
    private function matchByCityDisambiguation(): void
    {
        $remaining = DB::table('id_postal_codes')->whereNull('district_code')
            ->select('province_code', 'sub_district', 'city')->distinct()->get();

        foreach ($remaining as $row) {
            $target = $this->looseName($row->sub_district);

            $districts = DB::table('id_districts as d')
                ->join('id_regencies as r', 'r.code', '=', 'd.regency_code')
                ->where('r.province_code', $row->province_code)
                ->select('d.code', 'd.name', 'r.name as regency_name')
                ->get()
                ->filter(fn ($d) => $this->looseName($d->name) === $target)
                ->filter(fn ($d) => $this->normalizeRegencyName($d->regency_name) === Str::upper(trim($row->city)));

            if ($districts->count() === 1) {
                DB::table('id_postal_codes')
                    ->where('province_code', $row->province_code)
                    ->where('sub_district', $row->sub_district)
                    ->where('city', $row->city)
                    ->update(['district_code' => $districts->first()->code]);
            }
        }
    }

    private function looseName(string $name): string
    {
        $name = Str::before($name, '(');

        return Str::upper(str_replace([' ', '-', '.'], '', trim($name)));
    }

    private function normalizeRegencyName(string $name): string
    {
        return Str::upper(trim(preg_replace('/^(KABUPATEN|KOTA)\s+/i', '', trim($name))));
    }
}
