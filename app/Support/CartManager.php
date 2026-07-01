<?php

namespace App\Support;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the current cart (session-keyed for guests, user-keyed once
 * authenticated) and mutates its line items. Variant-aware: a product with
 * variants is only ever added by a specific variant, never the bare product.
 */
class CartManager
{
    public function current(): Cart
    {
        if (Auth::check()) {
            return Cart::firstOrCreate(['user_id' => Auth::id()]);
        }

        return Cart::firstOrCreate([
            'session_id' => session()->getId(),
            'user_id' => null,
        ]);
    }

    /**
     * Add a quantity of a product (or a specific variant) to the current cart.
     * Merges into an existing line if the same product/variant is already present.
     * Throws if the requested quantity would exceed available stock.
     */
    public function addItem(Product $product, int $quantity = 1, ?ProductVariant $variant = null): CartItem
    {
        $cart = $this->current();

        $item = $cart->items()
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variant?->id)
            ->first();

        $desiredQty = ($item?->quantity ?? 0) + $quantity;

        if (! $product->inStock($desiredQty, $variant)) {
            throw new \RuntimeException('Not enough stock available.');
        }

        if ($item) {
            $item->update(['quantity' => $desiredQty]);

            return $item->fresh();
        }

        return $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'quantity' => $quantity,
        ]);
    }

    public function updateQuantity(CartItem $item, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeItem($item);

            return;
        }

        $product = $item->product;
        $variant = $item->variant;

        if (! $product->inStock($quantity, $variant)) {
            throw new \RuntimeException('Not enough stock available.');
        }

        $item->update(['quantity' => $quantity]);
    }

    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    /** @return array{subtotal: int, currency: string, formatted: string, itemCount: int} */
    public function totals(): array
    {
        $cart = $this->current()->fresh('items.product', 'items.variant');

        $subtotal = $cart->items->sum(fn (CartItem $item) => $item->lineTotal());
        $currency = $cart->items->first()?->product?->currency ?? Money::DEFAULT_CURRENCY;

        return [
            'subtotal' => $subtotal,
            'currency' => $currency,
            'formatted' => Money::format($subtotal, $currency),
            'itemCount' => (int) $cart->items->sum('quantity'),
        ];
    }
}
