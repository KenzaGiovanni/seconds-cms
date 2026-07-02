<div class="cart-items-widget">
    @if ($errorMessage)
        <p class="cart-feedback cart-feedback--error">{{ $errorMessage }}</p>
    @endif

    @if ($items->isEmpty())
        <div class="empty-state">
            <p>Your cart is empty.</p>
            <a href="{{ route('shop.index') }}" wire:navigate class="btn-add-to-cart btn-link">Continue shopping</a>
        </div>
    @else
        <div class="cart-layout">
            {{-- Left: products --}}
            <div class="cart-products">
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

                <a href="{{ route('shop.index') }}" wire:navigate class="cart-continue-link">&larr; Continue shopping</a>
            </div>

            {{-- Right: summary --}}
            <aside class="cart-summary-panel">
                <h2 class="cart-summary-heading">Order summary</h2>

                {{-- Coupon --}}
                <div class="cart-coupon">
                    @if ($totals['couponCode'])
                        <div class="cart-coupon-applied">
                            <span>Coupon <strong>{{ $totals['couponCode'] }}</strong> applied</span>
                            <button type="button" wire:click="removeCoupon" class="cart-remove-btn">Remove</button>
                        </div>
                    @else
                        <div class="cart-coupon-form">
                            <input type="text" wire:model="couponInput" placeholder="Coupon code" class="qty-input cart-coupon-input">
                            <button type="button" wire:click="applyCoupon" class="cart-coupon-btn">Apply</button>
                        </div>
                        @if ($couponMessage)
                            <p class="cart-feedback cart-feedback--error">{{ $couponMessage }}</p>
                        @endif
                    @endif
                </div>

                <div class="cart-summary-lines">
                    <div class="cart-summary-line">
                        <span>Subtotal</span>
                        <span>{{ $totals['formatted'] }}</span>
                    </div>
                    @if ($totals['discount'] > 0)
                        <div class="cart-summary-line cart-summary-line--discount">
                            <span>Discount{{ $totals['discountLabel'] ? ' - '.$totals['discountLabel'] : '' }}</span>
                            <span>- {{ $totals['discountFormatted'] }}</span>
                        </div>
                    @endif
                    {{-- Shipping is chosen at checkout (delivery module, Phase 4). --}}
                    <div class="cart-summary-line cart-summary-line--muted">
                        <span>Shipping</span>
                        <span>Calculated at checkout</span>
                    </div>
                </div>

                <div class="cart-summary-total-row">
                    <span class="cart-summary-label">Total</span>
                    <span class="cart-summary-total">{{ $totals['totalFormatted'] }}</span>
                </div>

                <a href="{{ route('checkout.index') }}" wire:navigate class="btn-add-to-cart btn-link">Proceed to checkout</a>
            </aside>
        </div>
    @endif
</div>
