<div class="cart-items-widget">
    @if ($errorMessage)
        <p class="cart-feedback cart-feedback--error">{{ $errorMessage }}</p>
    @endif

    @if ($items->isEmpty())
        <div class="empty-state">
            <p>Your cart is empty.</p>
        </div>
    @else
        <div class="cart-table">
            @foreach ($items as $item)
                <div class="cart-row" wire:key="cart-item-{{ $item->id }}">
                    <div class="cart-row-info">
                        <p class="cart-row-name">{{ $item->product->name }}</p>
                        @if ($item->variant)
                            <p class="cart-row-variant">{{ $item->variant->label() }}</p>
                        @endif
                        <p class="cart-row-price">{{ \App\Support\Money::format($item->unitPrice(), $item->product->currency) }}</p>
                    </div>

                    <div class="cart-row-qty">
                        <input type="number" min="1" value="{{ $item->quantity }}"
                               wire:change="updateQuantity({{ $item->id }}, $event.target.value)"
                               class="qty-input">
                    </div>

                    <div class="cart-row-total">
                        {{ \App\Support\Money::format($item->lineTotal(), $item->product->currency) }}
                    </div>

                    <div class="cart-row-remove">
                        <button type="button" wire:click="removeItem({{ $item->id }})" class="cart-remove-btn">Remove</button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="cart-summary">
            <span class="cart-summary-label">Subtotal</span>
            <span class="cart-summary-total">{{ $totals['formatted'] }}</span>
        </div>

        <div class="cart-checkout-link">
            <a href="{{ route('checkout.index') }}" wire:navigate class="btn-add-to-cart btn-link">Proceed to checkout</a>
        </div>
    @endif
</div>
