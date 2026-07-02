<?php

use App\Console\Commands\ImportPostalCodes;
use App\Models\Region\District;
use App\Models\Region\PostalCode;
use App\Models\Region\Regency;
use Illuminate\Support\Facades\Schema;

function postalFixturePath(): string
{
    return base_path('tests/fixtures/postal_codes_sample.sql');
}

/** Seeds the province/regency/district hierarchy the postal fixture expects, including a same-named-district ambiguity. */
function seedPostalTestHierarchy(): void
{
    seedTestRegion(); // province 99 / regency 9901 "Test City" / district 990101 "Test District"

    $otherCity = Regency::firstOrCreate(['code' => '9903'], ['province_code' => '99', 'name' => 'Other City']);
    District::firstOrCreate(['code' => '990102'], ['regency_code' => '9901', 'name' => 'Shared District']);
    District::firstOrCreate(['code' => '990301'], ['regency_code' => $otherCity->code, 'name' => 'Shared District']);
    District::firstOrCreate(['code' => '990103'], ['regency_code' => '9901', 'name' => 'LooseDistrict']);
    District::firstOrCreate(['code' => '990104'], ['regency_code' => '9901', 'name' => 'AltName']);
}

it('imports postal codes and matches an unambiguous district by name', function () {
    seedPostalTestHierarchy();

    $this->artisan(ImportPostalCodes::class, ['--source' => postalFixturePath()])->assertSuccessful();

    expect(PostalCode::count())->toBe(7);

    $matched = PostalCode::where('postal_code', '99001')->first();
    expect($matched->district_code)->toBe('990101');
});

it('disambiguates a district name that repeats within the province using the city name', function () {
    seedPostalTestHierarchy();

    $this->artisan(ImportPostalCodes::class, ['--source' => postalFixturePath()])->assertSuccessful();

    $inTestCity = PostalCode::where('postal_code', '99101')->first();
    expect($inTestCity->district_code)->toBe('990102');

    $inOtherCity = PostalCode::where('postal_code', '99201')->first();
    expect($inOtherCity->district_code)->toBe('990301');
});

it('matches a district whose name differs only by spacing (loose pass)', function () {
    seedPostalTestHierarchy();

    $this->artisan(ImportPostalCodes::class, ['--source' => postalFixturePath()])->assertSuccessful();

    expect(PostalCode::where('postal_code', '99301')->first()->district_code)->toBe('990103');
});

it('matches a district after stripping a parenthetical alt-name', function () {
    seedPostalTestHierarchy();

    $this->artisan(ImportPostalCodes::class, ['--source' => postalFixturePath()])->assertSuccessful();

    expect(PostalCode::where('postal_code', '99401')->first()->district_code)->toBe('990104');
});

it('leaves a genuinely unmatched sub-district null rather than guessing', function () {
    seedPostalTestHierarchy();

    $this->artisan(ImportPostalCodes::class, ['--source' => postalFixturePath()])->assertSuccessful();

    $ghost = PostalCode::where('postal_code', '99901')->first();
    expect($ghost->district_code)->toBeNull();
});

it('fails cleanly when no source file is given', function () {
    $this->artisan(ImportPostalCodes::class)->assertFailed();
});

it('cleans up scratch tables after import', function () {
    seedPostalTestHierarchy();

    $this->artisan(ImportPostalCodes::class, ['--source' => postalFixturePath()])->assertSuccessful();

    expect(Schema::hasTable('db_province_data'))->toBeFalse();
    expect(Schema::hasTable('db_postal_code_data'))->toBeFalse();
});
