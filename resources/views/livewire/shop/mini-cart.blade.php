<a href="{{ route('cart.index') }}" class="mini-cart" wire:navigate>
    <span class="mini-cart-icon">Cart</span>
    @if ($totals['itemCount'] > 0)
        <span class="mini-cart-count">{{ $totals['itemCount'] }}</span>
    @endif
</a>
