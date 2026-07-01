<?php

namespace App\Livewire\Shop;

use App\Support\CartManager;
use Livewire\Attributes\On;
use Livewire\Component;

class MiniCart extends Component
{
    #[On('cart-updated')]
    public function refresh(): void
    {
        // Re-render only; totals are recomputed in render() from the DB.
    }

    public function render(CartManager $cart)
    {
        return view('livewire.shop.mini-cart', [
            'totals' => $cart->totals(),
        ]);
    }
}
