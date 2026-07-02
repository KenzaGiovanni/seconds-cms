<?php

use App\Delivery\Address;
use App\Delivery\KiriminAjaClient;
use App\Delivery\KiriminAjaProvider;
use App\Delivery\Parcel;
use App\Delivery\RateChoice;
use App\Enums\ShipmentStatus;
use App\Enums\ShippingProvider;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;

/** A KiriminAjaClient stub that returns canned data instead of hitting the real SDK. */
function fakeKiriminAjaClient(array $overrides = []): KiriminAjaClient
{
    return new class($overrides) extends KiriminAjaClient
    {
        public array $calls = [];

        public function __construct(private array $overrides) {}

        public function configure(string $apiKey, string $mode, ?string $baseUrl = null): void {}

        public function price($data): array
        {
            $this->calls['price'] = $data;

            return $this->overrides['price'] ?? ['results' => []];
        }

        public function requestPickup($data): array
        {
            $this->calls['requestPickup'] = $data;

            if (isset($this->overrides['requestPickup_throws'])) {
                throw new RuntimeException($this->overrides['requestPickup_throws']);
            }

            return $this->overrides['requestPickup'] ?? ['order_id' => 'KA-1', 'awb' => 'AWB-1'];
        }

        public function tracking(string $orderId): array
        {
            return $this->overrides['tracking'] ?? [];
        }

        public function creditBalance(): array
        {
            return $this->overrides['creditBalance'] ?? ['balance' => 100000];
        }
    };
}

function orderForShipping(): Order
{
    return Order::create([
        'status' => 'paid',
        'email' => 'cust@example.com',
        'customer_name' => 'Cust',
        'phone' => '0899999999',
        'shipping_address' => [
            'address' => 'Jl. Melati 2', 'city' => 'Bandung', 'postal' => '40123', 'subdistrict_id' => 88,
        ],
        'currency' => 'IDR',
        'subtotal' => 100000,
        'total' => 100000,
        'placed_at' => now(),
    ]);
}

beforeEach(function () {
    Setting::flushCache();
    Setting::set('delivery_origin_name', 'Seconds Store');
    Setting::set('delivery_origin_phone', '0811111111');
    Setting::set('delivery_origin_address', 'Jl. Origin 1');
    Setting::set('delivery_origin_subdistrict_id', '5');
    Setting::set('kiriminaja_api_key', 'test-key');
    Setting::set('kiriminaja_mode', 'staging');
    Setting::flushCache();
});

it('maps priced results into rate quotes', function () {
    $client = fakeKiriminAjaClient([
        'price' => ['results' => [
            ['courier' => 'jne', 'service' => 'reg', 'service_name' => 'JNE Reguler', 'cost' => 18000, 'etd' => '2-3 hari'],
            ['courier' => 'jnt', 'service' => 'ez', 'cost' => 15000],
            ['malformed' => true], // missing required keys - dropped
        ]],
    ]);
    $provider = new KiriminAjaProvider($client);

    $rates = $provider->rates(
        new Address('Origin', '0811111111', 'Jl. Origin 1', subdistrictId: 5),
        new Address('Dest', '0899999999', 'Jl. Melati 2', subdistrictId: 88),
        new Parcel(weightGrams: 1000),
    );

    expect($rates)->toHaveCount(2);
    expect($rates[0]->courier)->toBe('jne');
    expect($rates[0]->serviceName)->toBe('JNE Reguler');
    expect($rates[0]->cost)->toBe(18000);
    expect($rates[1]->serviceName)->toContain('JNT');
});

it('returns no rates when destination has no subdistrict id', function () {
    $provider = new KiriminAjaProvider(fakeKiriminAjaClient());

    $rates = $provider->rates(
        new Address('Origin', '0811111111', 'Jl. Origin 1', subdistrictId: 5),
        new Address('Dest', '0899999999', 'Jl. Melati 2', subdistrictId: null),
        new Parcel(weightGrams: 1000),
    );

    expect($rates)->toBe([]);
});

it('books a shipment via request_pickup and persists the returned awb', function () {
    $order = orderForShipping();
    $client = fakeKiriminAjaClient(['requestPickup' => ['order_id' => 'KA-77', 'awb' => 'JNE998877']]);
    $provider = new KiriminAjaProvider($client);

    $shipment = $provider->book($order, new RateChoice('jne', 'reg', 'JNE Reguler', 18000, 'IDR'));

    expect($shipment->provider)->toBe(ShippingProvider::Kiriminaja);
    expect($shipment->external_id)->toBe('KA-77');
    expect($shipment->tracking_number)->toBe('JNE998877');
    expect($shipment->status)->toBe(ShipmentStatus::Booked);
    expect($client->calls['requestPickup']->kecamatan_id)->toBe(5);
});

it('parses a valid webhook into a tracking event', function () {
    Setting::set('kiriminaja_webhook_token', 'secret-token');
    Setting::flushCache();

    $provider = new KiriminAjaProvider(fakeKiriminAjaClient());
    $request = Request::create('/webhooks/kiriminaja', 'POST', [
        'order_id' => 'KA-77', 'status' => 'delivered', 'updated_at' => '2026-07-02T10:00:00Z',
    ]);
    $request->headers->set('X-Kiriminaja-Token', 'secret-token');

    $event = $provider->handleWebhook($request);

    expect($event->externalId)->toBe('KA-77');
    expect($event->status)->toBe(ShipmentStatus::Delivered);
});

it('rejects a webhook with a bad token', function () {
    Setting::set('kiriminaja_webhook_token', 'secret-token');
    Setting::flushCache();

    $provider = new KiriminAjaProvider(fakeKiriminAjaClient());
    $request = Request::create('/webhooks/kiriminaja', 'POST', ['order_id' => 'KA-77', 'status' => 'delivered']);
    $request->headers->set('X-Kiriminaja-Token', 'wrong');

    $provider->handleWebhook($request);
})->throws(RuntimeException::class);

it('maps unknown webhook status strings to Booked rather than crashing', function () {
    Setting::set('kiriminaja_webhook_token', 'secret-token');
    Setting::flushCache();

    $provider = new KiriminAjaProvider(fakeKiriminAjaClient());
    $request = Request::create('/webhooks/kiriminaja', 'POST', ['order_id' => 'KA-1', 'status' => 'some_new_status']);
    $request->headers->set('X-Kiriminaja-Token', 'secret-token');

    expect($provider->handleWebhook($request)->status)->toBe(ShipmentStatus::Booked);
});
