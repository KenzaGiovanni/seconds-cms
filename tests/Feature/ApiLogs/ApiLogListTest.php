<?php

use App\Enums\Role;
use App\Livewire\Shop\ApiLogList;
use App\Models\ApiLog;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    Setting::set('ecommerce', 'true');
    Setting::flushCache();

    seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function apiLogsAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    return $user;
}

it('lists logged api calls for a permitted admin', function () {
    ApiLog::create([
        'provider' => 'xendit', 'direction' => 'outbound', 'method' => 'POST',
        'endpoint' => 'https://api.xendit.co/v2/invoices', 'successful' => true,
        'status_code' => 200, 'request_payload' => ['amount' => 1000], 'response_payload' => ['id' => 'inv_1'],
    ]);

    Livewire::actingAs(apiLogsAdmin())
        ->test(ApiLogList::class)
        ->assertSee('Xendit')
        ->assertSee('v2/invoices');
});

it('filters by provider, direction, and outcome', function () {
    ApiLog::create(['provider' => 'xendit', 'direction' => 'outbound', 'endpoint' => 'e1', 'successful' => true]);
    ApiLog::create(['provider' => 'kiriminaja', 'direction' => 'outbound', 'endpoint' => 'e2', 'successful' => false]);
    ApiLog::create(['provider' => 'kiriminaja', 'direction' => 'inbound', 'endpoint' => 'webhooks/kiriminaja', 'successful' => true]);

    $component = Livewire::actingAs(apiLogsAdmin())->test(ApiLogList::class);

    $component->set('provider', 'kiriminaja')->assertDontSee('>e1<', false);
    $component->set('direction', 'inbound')->assertDontSee('>e2<', false);
    $component->set('direction', 'all')->set('outcome', 'failed')->assertSee('e2');
});

it('expands a row to show request/response payloads', function () {
    $log = ApiLog::create([
        'provider' => 'xendit', 'direction' => 'outbound', 'endpoint' => 'e1', 'successful' => true,
        'request_payload' => ['amount' => 12345], 'response_payload' => ['id' => 'inv_xyz'],
    ]);

    Livewire::actingAs(apiLogsAdmin())
        ->test(ApiLogList::class)
        ->call('toggle', $log->id)
        ->assertSee('12345')
        ->assertSee('inv_xyz');
});

it('blocks a non-permitted user from the api log viewer', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    actingAs($editor)->get('/admin/shop/api-logs')->assertForbidden();
});
