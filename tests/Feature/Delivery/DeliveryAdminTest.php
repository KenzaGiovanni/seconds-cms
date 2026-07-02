<?php

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Enums\ShipmentStatus;
use App\Livewire\Shop\OrderDetail;
use App\Models\Order;
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

function paidOrderWithChosenDelivery(): Order
{
    $order = Order::create([
        'status' => 'awaiting_payment',
        'email' => 'a@example.com',
        'customer_name' => 'A Customer',
        'phone' => '0811111111',
        'shipping_address' => ['address_line' => 'Jl. X', 'city' => 'Jakarta', 'postal_code' => '12345'],
        'currency' => 'IDR',
        'subtotal' => 100000,
        'shipping_total' => 15000,
        'shipping_courier' => 'jne',
        'shipping_service_code' => 'reg',
        'shipping_service_name' => 'JNE Reguler',
        'total' => 115000,
    ]);
    $order->transitionTo(OrderStatus::Paid);

    return $order->fresh();
}

function deliveryAdminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    return $user;
}

it('books a shipment from the admin order screen using the checkout-time snapshot', function () {
    $order = paidOrderWithChosenDelivery();

    Livewire::actingAs(deliveryAdminUser())
        ->test(OrderDetail::class, ['id' => $order->id])
        ->call('bookShipment');

    $shipment = $order->shipments()->first();
    expect($shipment)->not->toBeNull();
    expect($shipment->courier)->toBe('jne');
    expect($shipment->status)->toBe(ShipmentStatus::Booked);
    // Booking alone does not advance the order.
    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('does not book a shipment before the order is paid', function () {
    $order = Order::create([
        'status' => 'pending',
        'email' => 'a@example.com',
        'customer_name' => 'A Customer',
        'shipping_address' => ['address_line' => 'X'],
        'shipping_courier' => 'jne', 'shipping_service_code' => 'reg', 'shipping_total' => 15000,
    ]);

    Livewire::actingAs(deliveryAdminUser())
        ->test(OrderDetail::class, ['id' => $order->id])
        ->call('bookShipment');

    expect($order->shipments()->count())->toBe(0);
});

it('does not double-book when clicking Book shipment twice', function () {
    $order = paidOrderWithChosenDelivery();

    $component = Livewire::actingAs(deliveryAdminUser())->test(OrderDetail::class, ['id' => $order->id]);
    $component->call('bookShipment');
    $component->call('bookShipment');

    expect($order->shipments()->count())->toBe(1);
});

it('admin manually advances a manual shipment through to delivered, completing the order', function () {
    $order = paidOrderWithChosenDelivery();

    $component = Livewire::actingAs(deliveryAdminUser())->test(OrderDetail::class, ['id' => $order->id]);
    $component->call('bookShipment');

    $shipment = $order->shipments()->first();

    $component->call('advanceShipment', $shipment->id, 'picked_up');
    expect($order->fresh()->status)->toBe(OrderStatus::Fulfilled);

    $component->call('advanceShipment', $shipment->id, 'delivered');
    expect($order->fresh()->status)->toBe(OrderStatus::Completed);
    expect($shipment->fresh()->status)->toBe(ShipmentStatus::Delivered);
});

it('blocks a non-permitted user from the order detail screen entirely', function () {
    $order = paidOrderWithChosenDelivery();

    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    actingAs($editor)->get('/admin/shop/orders/'.$order->id)->assertForbidden();
});
