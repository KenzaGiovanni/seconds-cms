<?php

use App\Delivery\KiriminAjaProvider;
use App\Delivery\ManualDeliveryProvider;
use App\Delivery\RateChoice;
use App\Delivery\ShipmentService;
use App\Delivery\TrackingEvent;
use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Enums\ShippingProvider;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Shipment;
use Illuminate\Support\Str;

/** Build a paid order with `$qty` of stock reserved, ready to fulfil. */
function paidOrder(int $stock = 10, int $qty = 2): Order
{
    $product = Product::create([
        'name' => 'Lamp', 'slug' => 'lamp-'.Str::lower(Str::random(6)), 'type' => 'simple',
        'status' => 'published', 'price' => 60000, 'stock_policy' => 'deny', 'stock' => $stock, 'sku' => 'L1',
    ]);
    $product->decrementStock($qty);

    $order = Order::create([
        'status' => OrderStatus::Pending,
        'email' => 'cust@example.com',
        'customer_name' => 'Cust',
        'phone' => '0800000000',
        'shipping_address' => ['address' => 'Jl. Mawar 1', 'city' => 'Jakarta', 'postal' => '12345'],
        'currency' => 'IDR',
        'subtotal' => 60000 * $qty,
        'total' => 60000 * $qty,
        'placed_at' => now(),
    ]);
    $order->items()->create([
        'product_id' => $product->id, 'name' => $product->name, 'sku' => 'L1',
        'unit_price' => 60000, 'quantity' => $qty, 'line_total' => 60000 * $qty, 'currency' => 'IDR',
    ]);

    // Walk it up to paid (the state fulfilment starts from).
    $order->transitionTo(OrderStatus::AwaitingPayment);
    $order->transitionTo(OrderStatus::Paid);

    return $order->fresh();
}

/** A KiriminAja-style shipment already booked with an external id (for webhook tests). */
function bookedRemoteShipment(Order $order, string $externalId = 'KA-123'): Shipment
{
    return Shipment::create([
        'order_id' => $order->id,
        'provider' => ShippingProvider::Kiriminaja,
        'courier' => 'jne', 'service_code' => 'reg',
        'external_id' => $externalId, 'tracking_number' => 'JNE999',
        'status' => ShipmentStatus::Booked, 'cost' => 12000, 'currency' => 'IDR',
        'booked_at' => now(),
    ]);
}

beforeEach(function () {
    Setting::flushCache();
});

it('defaults to the manual provider', function () {
    expect((new ShipmentService)->provider())->toBe(ShippingProvider::Manual);
    expect((new ShipmentService)->driver())->toBeInstanceOf(ManualDeliveryProvider::class);
});

it('manual provider offers a flat-rate quote and books an offline shipment', function () {
    Setting::set('delivery_flat_rate', 15000);
    Setting::flushCache();

    $order = paidOrder();
    $rates = (new ShipmentService)->rates($order);

    expect($rates)->toHaveCount(1);
    expect($rates[0]->cost)->toBe(15000);

    $shipment = (new ShipmentService)->book($order, new RateChoice(
        courier: 'jne', serviceCode: 'reg', serviceName: 'JNE Reguler',
        cost: 15000, currency: 'IDR', trackingNumber: 'JNE123',
    ));

    expect($shipment->provider)->toBe(ShippingProvider::Manual);
    expect($shipment->status)->toBe(ShipmentStatus::Booked);
    expect($shipment->tracking_number)->toBe('JNE123');
    // Booking alone does not advance the order.
    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('manual provider is free above the configured cart minimum in free-shipping mode', function () {
    Setting::set('delivery_flat_rate', 15000);
    Setting::set('delivery_manual_mode', 'free_shipping');
    Setting::set('delivery_free_shipping_minimum', 200000);
    Setting::flushCache();

    $order = paidOrder(qty: 1); // subtotal 60000 - below the 200000 minimum
    $belowMinimum = (new ShipmentService)->rates($order);
    expect($belowMinimum[0]->cost)->toBe(15000); // falls back to the flat rate

    $order->subtotal = 250000; // above the minimum
    $atOrAboveMinimum = (new ShipmentService)->rates($order);
    expect($atOrAboveMinimum[0]->cost)->toBe(0);
    expect($atOrAboveMinimum[0]->serviceName)->toBe('Free shipping');
});

it('manual provider always charges the flat rate in single-rate mode regardless of cart size', function () {
    Setting::set('delivery_flat_rate', 15000);
    Setting::set('delivery_manual_mode', 'flat');
    Setting::set('delivery_free_shipping_minimum', 1); // would trivially qualify if mode were free_shipping
    Setting::flushCache();

    $order = paidOrder(qty: 1);
    $order->subtotal = 5000000;
    $rates = (new ShipmentService)->rates($order);

    expect($rates[0]->cost)->toBe(15000);
});

it('does not double-book an order that already has an active shipment', function () {
    $order = paidOrder();
    $svc = new ShipmentService;
    $choice = new RateChoice('jne', 'reg', 'JNE Reguler', 15000, 'IDR', 'JNE123');

    $first = $svc->book($order, $choice);
    $second = $svc->book($order, $choice);

    expect($second->id)->toBe($first->id);
    expect($order->shipments()->count())->toBe(1);
});

it('manual advance moves the order to fulfilled then completed', function () {
    $order = paidOrder();
    $svc = new ShipmentService;
    $shipment = $svc->book($order, new RateChoice('jne', 'reg', 'JNE Reguler', 15000, 'IDR', 'JNE123'));

    $svc->advanceManual($shipment, ShipmentStatus::PickedUp);
    expect($order->fresh()->status)->toBe(OrderStatus::Fulfilled);
    expect($shipment->fresh()->picked_up_at)->not->toBeNull();

    $svc->advanceManual($shipment, ShipmentStatus::Delivered);
    expect($order->fresh()->status)->toBe(OrderStatus::Completed);
    expect($shipment->fresh()->delivered_at)->not->toBeNull();
});

it('applies a tracking webhook idempotently and monotonically', function () {
    $order = paidOrder();
    $shipment = bookedRemoteShipment($order);
    $svc = new ShipmentService;

    $picked = new TrackingEvent('KA-123', ShipmentStatus::PickedUp, 'sig-1', ['x' => 1]);

    $svc->applyTrackingEvent($picked);
    expect($shipment->fresh()->status)->toBe(ShipmentStatus::PickedUp);
    expect($order->fresh()->status)->toBe(OrderStatus::Fulfilled);

    // Replaying the same event changes nothing.
    $svc->applyTrackingEvent($picked);
    expect($shipment->fresh()->status)->toBe(ShipmentStatus::PickedUp);
    expect($order->fresh()->status)->toBe(OrderStatus::Fulfilled);

    // An out-of-order older event (Booked) is dropped.
    $svc->applyTrackingEvent(new TrackingEvent('KA-123', ShipmentStatus::Booked, 'sig-0'));
    expect($shipment->fresh()->status)->toBe(ShipmentStatus::PickedUp);

    // A forward event advances and completes the order.
    $svc->applyTrackingEvent(new TrackingEvent('KA-123', ShipmentStatus::Delivered, 'sig-2'));
    expect($shipment->fresh()->status)->toBe(ShipmentStatus::Delivered);
    expect($order->fresh()->status)->toBe(OrderStatus::Completed);

    // A late "delivered" replay after final state is a safe no-op.
    $svc->applyTrackingEvent(new TrackingEvent('KA-123', ShipmentStatus::Delivered, 'sig-3'));
    expect($shipment->fresh()->status)->toBe(ShipmentStatus::Delivered);
    expect($order->fresh()->status)->toBe(OrderStatus::Completed);
});

it('delivered from paid walks the order straight through to completed', function () {
    $order = paidOrder();
    $shipment = bookedRemoteShipment($order);

    (new ShipmentService)->applyTrackingEvent(new TrackingEvent('KA-123', ShipmentStatus::Delivered, 'sig-1'));

    expect($order->fresh()->status)->toBe(OrderStatus::Completed);
});

it('ignores a tracking webhook for an unknown external id', function () {
    $order = paidOrder();
    bookedRemoteShipment($order);

    (new ShipmentService)->applyTrackingEvent(new TrackingEvent('KA-UNKNOWN', ShipmentStatus::Delivered, 'sig-1'));

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('does not advance a cancelled/refunded order via delivery', function () {
    $order = paidOrder();
    $shipment = bookedRemoteShipment($order);

    // Order refunded out of band; a stray delivery event must not touch it.
    $order->transitionTo(OrderStatus::Refunded);

    (new ShipmentService)->applyTrackingEvent(new TrackingEvent('KA-123', ShipmentStatus::Delivered, 'sig-1'));

    expect($order->fresh()->status)->toBe(OrderStatus::Refunded);
    // Shipment status still records the courier event even if the order is frozen.
    expect($shipment->fresh()->status)->toBe(ShipmentStatus::Delivered);
});

it('manual delivery provider has no webhook', function () {
    (new ManualDeliveryProvider)->handleWebhook(request());
})->throws(LogicException::class);

it('resolves the kiriminaja driver once activated', function () {
    Setting::set('delivery_provider', ShippingProvider::Kiriminaja->value);
    Setting::flushCache();

    expect((new ShipmentService)->driver())->toBeInstanceOf(KiriminAjaProvider::class);
});
