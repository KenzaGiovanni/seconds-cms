<?php

use App\Enums\OrderStatus;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Setting;
use App\Support\CartManager;
use App\Support\CheckoutService;

beforeEach(function () {
    Setting::set('ecommerce', 'true');
    Setting::flushCache();
});

function seedProductInCart(int $price = 100000, int $qty = 2, string $policy = 'none'): Product
{
    $product = Product::create([
        'name' => 'Item', 'slug' => 'item-'.uniqid(), 'type' => 'simple',
        'status' => 'published', 'price' => $price, 'stock_policy' => $policy, 'stock' => 100,
    ]);

    app(CartManager::class)->addItem($product, $qty);

    return $product;
}

function place(): Order
{
    return app(CheckoutService::class)->placeOrder(
        ['name' => 'Buyer', 'email' => 'buyer@example.com'],
        ['address_line' => 'A', 'city' => 'B', 'postal_code' => 'C'],
    );
}

it('applies an automatic discount at checkout and reduces the order total', function () {
    seedProductInCart(100000, 2);
    Promotion::create([
        'name' => 'Auto 20', 'type' => 'automatic',
        'discount_type' => 'percentage', 'discount_value' => 20,
    ]);

    $order = place();

    // 2 x 100000 = 200000, 20% off each = 40000 discount.
    expect($order->subtotal)->toBe(200000)
        ->and($order->discount_total)->toBe(40000)
        ->and($order->total)->toBe(160000)
        ->and($order->discount_units)->toBe(2);
});

it('consumes the global quota when an order is placed', function () {
    seedProductInCart(100000, 3);
    $promo = Promotion::create([
        'name' => 'Quota', 'type' => 'automatic',
        'discount_type' => 'fixed', 'discount_value' => 10000,
        'usage_quota' => 10,
    ]);

    place();

    expect($promo->fresh()->usage_count)->toBe(3);
});

it('applies a coupon entered on the cart and records the redemption', function () {
    seedProductInCart(100000, 1);
    $promo = Promotion::create([
        'name' => 'Coupon 30', 'type' => 'coupon',
        'discount_type' => 'percentage', 'discount_value' => 30,
    ]);
    $coupon = Coupon::create(['promotion_id' => $promo->id, 'code' => 'SAVE30', 'max_uses' => 5]);

    app(CartManager::class)->applyCoupon('SAVE30');

    $order = place();

    expect($order->discount_total)->toBe(30000)
        ->and($order->coupon_id)->toBe($coupon->id)
        ->and($order->coupon_code)->toBe('SAVE30')
        ->and($coupon->fresh()->used_count)->toBe(1);
});

it('does not discount once the quota is exhausted', function () {
    seedProductInCart(100000, 2);
    Promotion::create([
        'name' => 'Spent', 'type' => 'automatic',
        'discount_type' => 'percentage', 'discount_value' => 20,
        'usage_quota' => 10, 'usage_count' => 10,
    ]);

    $order = place();

    expect($order->discount_total)->toBe(0)
        ->and($order->total)->toBe(200000);
});

it('releases the promotion quota and coupon use when the order is cancelled', function () {
    seedProductInCart(100000, 2);
    $promo = Promotion::create([
        'name' => 'Releasable', 'type' => 'coupon',
        'discount_type' => 'percentage', 'discount_value' => 25,
        'usage_quota' => 10,
    ]);
    $coupon = Coupon::create(['promotion_id' => $promo->id, 'code' => 'REL25', 'max_uses' => 5]);

    app(CartManager::class)->applyCoupon('REL25');
    $order = place();

    expect($promo->fresh()->usage_count)->toBe(2)
        ->and($coupon->fresh()->used_count)->toBe(1);

    $order->transitionTo(OrderStatus::Cancelled);

    expect($promo->fresh()->usage_count)->toBe(0)
        ->and($coupon->fresh()->used_count)->toBe(0);
});

it('clears the applied coupon from the session after a successful order', function () {
    seedProductInCart(100000, 1);
    $promo = Promotion::create([
        'name' => 'Once', 'type' => 'coupon',
        'discount_type' => 'percentage', 'discount_value' => 10,
    ]);
    Coupon::create(['promotion_id' => $promo->id, 'code' => 'ONCE']);

    app(CartManager::class)->applyCoupon('ONCE');
    expect(app(CartManager::class)->couponCode())->toBe('ONCE');

    place();

    expect(app(CartManager::class)->couponCode())->toBeNull();
});

it('reflects the discount in cart totals', function () {
    seedProductInCart(100000, 2);
    Promotion::create([
        'name' => 'Cart 10', 'type' => 'automatic',
        'discount_type' => 'percentage', 'discount_value' => 10,
    ]);

    $totals = app(CartManager::class)->totals();

    expect($totals['discount'])->toBe(20000)
        ->and($totals['total'])->toBe(180000);
});
