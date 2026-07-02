<?php

use App\Console\Commands\ReconcileKiriminAjaShipments;
use App\Delivery\KiriminAjaClient;
use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Enums\ShippingProvider;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Shipment;
use App\Support\DeliverySettings;

beforeEach(function () {
    Setting::set('ecommerce', 'true');
    DeliverySettings::setKiriminajaKeys('test-key', 'staging', 'wh-secret');
    Setting::flushCache();
});

function orderWithRemoteShipment(string $status = 'booked', string $externalId = 'KA-500'): array
{
    $order = Order::create([
        'status' => 'paid',
        'email' => 'a@example.com',
        'customer_name' => 'A',
        'currency' => 'IDR',
        'subtotal' => 50000,
        'total' => 50000,
        'placed_at' => now(),
    ]);

    $shipment = Shipment::create([
        'order_id' => $order->id,
        'provider' => ShippingProvider::Kiriminaja,
        'courier' => 'jne', 'service_code' => 'reg',
        'external_id' => $externalId, 'tracking_number' => 'JNE1',
        'status' => $status, 'cost' => 15000, 'currency' => 'IDR',
        'booked_at' => now(),
    ]);
    $shipment->forceFill(['updated_at' => now()->subMinutes(10)])->save();

    return [$order, $shipment];
}

it('a valid webhook advances the shipment and reflects on the order', function () {
    [$order, $shipment] = orderWithRemoteShipment();

    $this->postJson('/webhooks/kiriminaja', [
        'order_id' => $shipment->external_id, 'status' => 'delivered',
    ], ['X-Kiriminaja-Token' => 'wh-secret'])->assertOk();

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::Delivered);
    expect($order->fresh()->status)->toBe(OrderStatus::Completed);
});

it('rejects a webhook with a bad or missing token', function () {
    [, $shipment] = orderWithRemoteShipment();

    $this->postJson('/webhooks/kiriminaja', [
        'order_id' => $shipment->external_id, 'status' => 'delivered',
    ], ['X-Kiriminaja-Token' => 'wrong'])->assertStatus(401);

    $this->postJson('/webhooks/kiriminaja', [
        'order_id' => $shipment->external_id, 'status' => 'delivered',
    ])->assertStatus(401);

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::Booked);
});

it('ignores a webhook for an unknown external id', function () {
    orderWithRemoteShipment();

    $this->postJson('/webhooks/kiriminaja', [
        'order_id' => 'UNKNOWN', 'status' => 'delivered',
    ], ['X-Kiriminaja-Token' => 'wh-secret'])->assertOk();
});

it('404s the webhook route when ecommerce is off', function () {
    Setting::set('ecommerce', 'false');
    Setting::flushCache();

    $this->postJson('/webhooks/kiriminaja', [
        'order_id' => 'X', 'status' => 'delivered',
    ], ['X-Kiriminaja-Token' => 'wh-secret'])->assertNotFound();
});

it('reconcile command updates a stale shipment but leaves a fresh one alone', function () {
    [$order, $stale] = orderWithRemoteShipment(status: 'booked', externalId: 'KA-STALE');
    $fresh = Shipment::create([
        'order_id' => $order->id, 'provider' => ShippingProvider::Kiriminaja,
        'courier' => 'jne', 'service_code' => 'reg', 'external_id' => 'KA-FRESH',
        'status' => 'booked', 'cost' => 15000, 'currency' => 'IDR', 'booked_at' => now(),
    ]);

    $fakeClient = new class extends KiriminAjaClient
    {
        public function configure(string $apiKey, string $mode, ?string $baseUrl = null): void {}

        public function tracking(string $orderId): array
        {
            return $orderId === 'KA-STALE'
                ? ['history' => [['status' => 'picked_up', 'datetime' => now()->toDateTimeString()]]]
                : ['history' => []];
        }
    };
    $this->app->instance(KiriminAjaClient::class, $fakeClient);

    $this->artisan(ReconcileKiriminAjaShipments::class)->assertSuccessful();

    expect($stale->fresh()->status)->toBe(ShipmentStatus::PickedUp);
    expect($fresh->fresh()->status)->toBe(ShipmentStatus::Booked); // not stale enough / no update returned
});
