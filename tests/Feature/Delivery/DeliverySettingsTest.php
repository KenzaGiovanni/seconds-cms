<?php

use App\Delivery\Address;
use App\Delivery\KiriminAjaClient;
use App\Delivery\KiriminAjaProvider;
use App\Delivery\Parcel;
use App\Enums\Role;
use App\Enums\ShipmentStatus;
use App\Livewire\Shop\DeliverySettingsForm;
use App\Livewire\Shop\OrderDetail;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Support\DeliverySettings;
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

function deliverySettingsAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    return $user;
}

it('persists origin address and parcel defaults', function () {
    Livewire::actingAs(deliverySettingsAdmin())
        ->test(DeliverySettingsForm::class)
        ->set('originName', 'Seconds Store')
        ->set('originPhone', '0811111111')
        ->set('originAddress', 'Jl. Origin 1')
        ->set('originCity', 'Jakarta')
        ->set('originPostal', '12345')
        ->set('originSubdistrictId', 88)
        ->set('defaultWeight', 1500)
        ->set('flatRate', 20000)
        ->call('save');

    $origin = DeliverySettings::origin();
    expect($origin->name)->toBe('Seconds Store');
    expect($origin->subdistrictId)->toBe(88);
    expect(DeliverySettings::defaultWeightGrams())->toBe(1500);
    expect(DeliverySettings::flatRate())->toBe(20000);
});

it('activates kiriminaja after verifying the key, masks it on redisplay, and can switch back', function () {
    $fakeClient = new class extends KiriminAjaClient
    {
        public function configure(string $apiKey, string $mode, ?string $baseUrl = null): void {}

        public function creditBalance(): array
        {
            return ['balance' => 500000];
        }
    };
    $this->app->instance(KiriminAjaClient::class, $fakeClient);

    $component = Livewire::actingAs(deliverySettingsAdmin())
        ->test(DeliverySettingsForm::class)
        ->set('kiriminajaApiKey', 'live-key-1234')
        ->set('kiriminajaMode', 'production')
        ->set('enabledCouriers', 'jne, jnt')
        ->call('activateKiriminaja');

    expect(DeliverySettings::provider()->value)->toBe('kiriminaja');
    expect(DeliverySettings::enabledCouriers())->toBe(['jne', 'jnt']);
    expect(DeliverySettings::maskedKiriminajaApiKey())->toContain('1234');

    $component->call('useManualDelivery');
    expect(DeliverySettings::provider()->value)->toBe('manual');
});

it('rejects kiriminaja activation when the key does not verify', function () {
    $fakeClient = new class extends KiriminAjaClient
    {
        public function configure(string $apiKey, string $mode, ?string $baseUrl = null): void {}

        public function creditBalance(): array
        {
            throw new RuntimeException('unauthorized');
        }
    };
    $this->app->instance(KiriminAjaClient::class, $fakeClient);

    Livewire::actingAs(deliverySettingsAdmin())
        ->test(DeliverySettingsForm::class)
        ->set('kiriminajaApiKey', 'bad-key')
        ->call('activateKiriminaja');

    expect(DeliverySettings::provider()->value)->toBe('manual');
});

it('blocks a non-permitted user from delivery settings', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    actingAs($editor)->get('/admin/shop/delivery/settings')->assertForbidden();
});

it('admin adds a manual shipment by hand regardless of the active provider', function () {
    Setting::set('delivery_provider', 'kiriminaja');
    Setting::flushCache();

    $order = Order::create([
        'status' => 'paid', 'email' => 'a@example.com', 'customer_name' => 'A',
        'shipping_address' => ['address_line' => 'X'], 'currency' => 'IDR',
        'subtotal' => 50000, 'total' => 50000,
    ]);

    Livewire::actingAs(deliverySettingsAdmin())
        ->test(OrderDetail::class, ['id' => $order->id])
        ->set('manualCourier', 'JNE')
        ->set('manualServiceName', 'JNE Reguler')
        ->set('manualTrackingNumber', 'JNE555')
        ->set('manualCost', 18000)
        ->call('addManualShipment');

    $shipment = $order->shipments()->first();
    expect($shipment->provider->value)->toBe('manual');
    expect($shipment->tracking_number)->toBe('JNE555');
    expect($shipment->status)->toBe(ShipmentStatus::Booked);
});

it('filters checkout rates to enabled couriers when kiriminaja is active', function () {
    Setting::set('delivery_provider', 'kiriminaja');
    Setting::set('kiriminaja_enabled_couriers', 'jne');
    Setting::flushCache();

    $fakeClient = new class extends KiriminAjaClient
    {
        public array $lastPriceData;

        public function configure(string $apiKey, string $mode, ?string $baseUrl = null): void {}

        public function price($data): array
        {
            $this->lastPriceData = (array) $data->courier;

            return ['results' => []];
        }
    };
    $this->app->instance(KiriminAjaClient::class, $fakeClient);

    $provider = new KiriminAjaProvider($fakeClient);
    $provider->rates(
        new Address('Origin', '0811111111', 'Jl. Origin', subdistrictId: 5),
        new Address('Dest', '0899999999', 'Jl. Dest', subdistrictId: 88),
        new Parcel(weightGrams: 1000),
    );

    expect($fakeClient->lastPriceData)->toBe(['jne']);
});
