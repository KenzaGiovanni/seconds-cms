<?php

use App\Console\Commands\ImportIndonesiaRegions;
use App\Models\Region\District;
use App\Models\Region\Province;
use App\Models\Region\Regency;
use App\Models\Region\Village;
use Illuminate\Support\Facades\Schema;

function fixturePath(): string
{
    return base_path('tests/fixtures/wilayah_sample.sql');
}

it('imports the province/regency/district/village hierarchy from a dump', function () {
    $this->artisan(ImportIndonesiaRegions::class, ['--source' => fixturePath()])->assertSuccessful();

    expect(Province::count())->toBe(1);
    expect(Regency::count())->toBe(2);
    expect(District::count())->toBe(2);
    expect(Village::count())->toBe(1);

    $province = Province::find('99');
    expect($province->name)->toBe('Test Province');
    expect($province->regencies)->toHaveCount(2);

    $regencyA = Regency::find('9901');
    expect($regencyA->province->code)->toBe('99');
    expect($regencyA->districts)->toHaveCount(1);

    $districtA1 = District::find('990101');
    expect($districtA1->regency->code)->toBe('9901');
    expect($districtA1->kiriminaja_subdistrict_id)->toBeNull();

    $village = Village::find('9901012001');
    expect($village->district->code)->toBe('990101');
    expect((float) $village->latitude)->toBeGreaterThan(2.9);
});

it('cleans up the scratch tables after import', function () {
    $this->artisan(ImportIndonesiaRegions::class, ['--source' => fixturePath()])->assertSuccessful();

    expect(Schema::hasTable('t_provinsi'))->toBeFalse();
    expect(Schema::hasTable('t_kota'))->toBeFalse();
    expect(Schema::hasTable('t_kecamatan'))->toBeFalse();
    expect(Schema::hasTable('t_kelurahan'))->toBeFalse();
});

it('re-importing preserves a kiriminaja_subdistrict_id backfill on districts', function () {
    $this->artisan(ImportIndonesiaRegions::class, ['--source' => fixturePath()])->assertSuccessful();

    District::where('code', '990101')->update(['kiriminaja_subdistrict_id' => 4242]);

    $this->artisan(ImportIndonesiaRegions::class, ['--source' => fixturePath()])->assertSuccessful();

    expect(District::find('990101')->kiriminaja_subdistrict_id)->toBe(4242);
    expect(District::count())->toBe(2); // no duplicates from the re-run
});

it('re-importing does not duplicate provinces/regencies/villages', function () {
    $this->artisan(ImportIndonesiaRegions::class, ['--source' => fixturePath()])->assertSuccessful();
    $this->artisan(ImportIndonesiaRegions::class, ['--source' => fixturePath()])->assertSuccessful();

    expect(Province::count())->toBe(1);
    expect(Regency::count())->toBe(2);
    expect(Village::count())->toBe(1);
});
