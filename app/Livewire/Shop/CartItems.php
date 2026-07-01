<?php

namespace App\Livewire\Shop;

use App\Support\CartManager;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The interactive cart list: quantity updates, line removal, totals.
 * Embedded on the storefront cart page via @livewire('shop.cart-items').
 */
class CartItems extends Component
{
    public ?string $errorMessage = null;

    public string $couponInput = '';

    public ?string $couponMessage = null;

    #[On('cart-updated')]
    public function refresh(): void
    {
        // Re-render only; state is recomputed in render() from the DB.
    }

    public function applyCoupon(CartManager $cart): void
    {
        $this->couponMessage = null;

        if (trim($this->couponInput) === '') {
            return;
        }

        $result = $cart->applyCoupon($this->couponInput);

        if ($result->hasDiscount()) {
            $this->couponInput = '';
            $this->dispatch('cart-updated');
        } else {
            $this->couponMessage = 'That code is not valid for your cart.';
        }
    }

    public function removeCoupon(CartManager $cart): void
    {
        $cart->removeCoupon();
        $this->couponMessage = null;
        $this->dispatch('cart-updated');
    }

    public function updateQuantity(CartManager $cart, int $itemId, int $quantity): void
    {
        $this->errorMessage = null;

        $item = $cart->current()->items()->findOrFail($itemId);

        try {
            $cart->updateQuantity($item, $quantity);
            $this->dispatch('cart-updated');
        } catch (\RuntimeException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function removeItem(CartManager $cart, int $itemId): void
    {
        $item = $cart->current()->items()->findOrFail($itemId);
        $cart->removeItem($item);
        $this->dispatch('cart-updated');
    }

    public function render(CartManager $cart)
    {
        $current = $cart->current()->fresh(['items.product', 'items.variant']);

        return view('livewire.shop.cart-items', [
            'items' => $current->items,
            'totals' => $cart->totals(),
        ]);
    }
}
