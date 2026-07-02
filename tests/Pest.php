<?php

use App\Models\Region\District;
use App\Models\Region\Province;
use App\Models\Region\Regency;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Bind the base TestCase to the Feature and Unit suites so Pest tests get
| the full Laravel application bootstrapped. Feature tests run against a
| migrated MySQL `seconds_test` DB, refreshed per test.
|
*/

pest()->extend(TestCase::class)->in('Unit');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => Setting::flushCache())
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Region test helper
|--------------------------------------------------------------------------
|
| Checkout's shipping-address form validates against the real
| id_provinces/id_regencies/id_districts tables (the regions:import-indonesia
| dataset). Rather than running that import in every test, seed one minimal
| region row directly - shared across test files so it's defined exactly once.
|
*/
function seedTestRegion(): array
{
    $province = Province::firstOrCreate(['code' => '99'], ['name' => 'Test Province']);
    $regency = Regency::firstOrCreate(['code' => '9901'], ['province_code' => '99', 'name' => 'Test City']);
    $district = District::firstOrCreate(['code' => '990101'], ['regency_code' => '9901', 'name' => 'Test District']);

    return [
        'provinceCode' => $province->code,
        'regencyCode' => $regency->code,
        'districtCode' => $district->code,
    ];
}
