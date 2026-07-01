<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Converts the current cart into an order: snapshots price/name/options onto
 * OrderItem rows (so later catalog edits never change a past order), decrements
 * stock, and moves the order to awaiting_payment. Payment itself is Phase 3 -
 * for now every order simply waits for a manual "mark paid" (Phase 2.5/3).
 */
class CheckoutService
{
    public function __construct(private CartManager $cartManager) {}

    /**
     * @param  array{name: string, email: string, phone?: string}  $customer
     * @param  array<string, mixed>  $shippingAddress
     */
    public function placeOrder(array $customer, array $shippingAddress, ?string $notes = null): Order
    {
        $cart = $this->cartManager->current()->fresh(['items.product', 'items.variant']);

        if ($cart->items->isEmpty()) {
            throw new \RuntimeException('Your cart is empty.');
        }

        return DB::transaction(function () use ($cart, $customer, $shippingAddress, $notes) {
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
            $order->recalculateTotals();
            $order->save();
            $order->transitionTo(OrderStatus::AwaitingPayment);

            $cart->items()->delete();

            return $order;
        });
    }
}
