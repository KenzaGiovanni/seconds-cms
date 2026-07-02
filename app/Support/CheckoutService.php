<?php

namespace App\Support;

use App\Delivery\RateQuote;
use App\Enums\OrderStatus;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Promotion;
use App\Payments\PaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Converts the current cart into an order: snapshots price/name/options onto
 * OrderItem rows (so later catalog edits never change a past order), decrements
 * stock, applies any promotion/coupon discount, moves the order to
 * awaiting_payment, and initiates payment through the active gateway (manual
 * bank transfer by default - Phase 3.1).
 */
class CheckoutService
{
    public function __construct(
        private CartManager $cartManager,
        private DiscountCalculator $discounts,
        private PaymentService $payments,
    ) {}

    /**
     * @param  array{name: string, email: string, phone?: string}  $customer
     * @param  array<string, mixed>  $shippingAddress
     */
    public function placeOrder(array $customer, array $shippingAddress, ?string $notes = null, ?RateQuote $shipping = null): Order
    {
        $cart = $this->cartManager->current()->fresh(['items.product', 'items.variant']);

        if ($cart->items->isEmpty()) {
            throw new \RuntimeException('Your cart is empty.');
        }

        $couponCode = $this->cartManager->couponCode();

        return DB::transaction(function () use ($cart, $customer, $shippingAddress, $notes, $couponCode, $shipping) {
            $order = Order::create([
                'status' => OrderStatus::Pending,
                'user_id' => Auth::id(),
                'email' => $customer['email'],
                'customer_name' => $customer['name'],
                'phone' => $customer['phone'] ?? null,
                'shipping_address' => $shippingAddress,
                'billing_address' => $shippingAddress,
                'currency' => Money::DEFAULT_CURRENCY,
                'notes' => $notes,
                'placed_at' => now(),
                'shipping_total' => $shipping?->cost ?? 0,
                'shipping_courier' => $shipping?->courier,
                'shipping_service_code' => $shipping?->serviceCode,
                'shipping_service_name' => $shipping?->serviceName,
            ]);

            foreach ($cart->items as $item) {
                $product = $item->product;
                $variant = $item->variant;

                if (! $product->inStock($item->quantity, $variant)) {
                    throw new \RuntimeException("\"{$product->name}\" no longer has enough stock.");
                }

                $unitPrice = $item->unitPrice();

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'name' => $product->name,
                    'sku' => $variant?->sku ?? $product->sku,
                    'unit_price' => $unitPrice,
                    'quantity' => $item->quantity,
                    'line_total' => $unitPrice * $item->quantity,
                    'currency' => $product->currency,
                    'options' => $variant?->options,
                ]);

                $product->decrementStock($item->quantity, $variant);
            }

            $order->load('items');
            $this->applyDiscount($order, $cart, $couponCode);
            $order->recalculateTotals();
            $order->save();
            $order->transitionTo(OrderStatus::AwaitingPayment);
            $this->payments->initiate($order);

            $cart->items()->delete();
            $this->cartManager->removeCoupon();

            // Order confirmation email is stubbed until mail is configured (matches Forms module convention).
            return $order;
        });
    }

    /**
     * Recompute the discount against a row-locked promotion (so the global quota
     * can't be oversold under normal concurrency), snapshot it onto the order,
     * and consume the promotion quota + coupon use. The lock serialises
     * concurrent checkouts on the same promotion.
     */
    private function applyDiscount(Order $order, $cart, ?string $couponCode): void
    {
        $lines = $cart->items->map(fn ($item) => [
            'price' => $item->unitPrice(),
            'qty' => (int) $item->quantity,
        ])->all();

        // Who won (auto vs coupon), before locking.
        $winner = $this->discounts->calculate($lines, $couponCode);
        if (! $winner->hasDiscount() || ! $winner->promotion) {
            return;
        }

        $promo = Promotion::whereKey($winner->promotion->id)->lockForUpdate()->first();
        if (! $promo || ! $promo->isActiveNow()) {
            return;
        }

        $coupon = null;
        if ($winner->coupon) {
            $coupon = Coupon::whereKey($winner->coupon->id)->lockForUpdate()->first();
            if (! $coupon || ! $coupon->hasUsesLeft()) {
                return;
            }
        }

        // Recompute with the freshest quota now that the row is locked.
        $applied = $this->discounts->computeForPromotion($promo, $coupon, $lines);
        if (! $applied->hasDiscount()) {
            return;
        }

        $order->discount_total = $applied->discountTotal;
        $order->promotion_id = $promo->id;
        $order->coupon_id = $coupon?->id;
        $order->coupon_code = $coupon?->code;
        $order->discount_units = $applied->discountUnits;

        $promo->usage_count += $applied->discountUnits;
        $promo->save();

        if ($coupon) {
            $coupon->used_count += 1;
            $coupon->save();
        }
    }
}
