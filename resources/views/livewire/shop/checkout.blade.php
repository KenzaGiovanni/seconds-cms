<div class="checkout-widget">
    @if ($errorMessage)
        <p class="cart-feedback cart-feedback--error">{{ $errorMessage }}</p>
    @endif

    <form wire:submit="placeOrder" class="checkout-grid">
        <div class="checkout-fields">
            <h2 class="checkout-heading">Contact</h2>
            <div class="checkout-field">
                <label>Full name</label>
                <input type="text" wire:model="name">
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="checkout-field">
                <label>Email</label>
                <input type="email" wire:model="email">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="checkout-field">
                <label>Phone <span class="optional">(optional)</span></label>
                <input type="text" wire:model="phone">
                @error('phone') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <h2 class="checkout-heading">Shipping address</h2>
            <div class="checkout-field">
                <label>Address</label>
                <input type="text" wire:model="addressLine">
                @error('addressLine') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="checkout-field-row">
                <div class="checkout-field">
                    <label>City</label>
                    <input type="text" wire:model="city">
                    @error('city') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="checkout-field">
                    <label>Postal code</label>
                    <input type="text" wire:model="postalCode">
                    @error('postalCode') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="checkout-field">
                <label>Order notes <span class="optional">(optional)</span></label>
                <textarea wire:model="notes" rows="3"></textarea>
            </div>

            {{-- Delivery method (UI only - wired in Phase 4, KiriminAja live rates) --}}
            <h2 class="checkout-heading">Delivery method</h2>
            <div class="checkout-options" aria-label="Delivery method">
                <label class="checkout-option checkout-option--selected">
                    <input type="radio" name="delivery" checked disabled>
                    <span class="checkout-option-body">
                        <span class="checkout-option-title">Standard delivery</span>
                        <span class="checkout-option-sub">Live courier rates appear here once delivery is connected</span>
                    </span>
                    <span class="checkout-option-price">-</span>
                </label>
                <label class="checkout-option checkout-option--disabled">
                    <input type="radio" name="delivery" disabled>
                    <span class="checkout-option-body">
                        <span class="checkout-option-title">Instant / same-day</span>
                        <span class="checkout-option-sub">Coming soon</span>
                    </span>
                    <span class="checkout-option-price">-</span>
                </label>
            </div>
            <p class="checkout-note">Courier options and live rates are added in the delivery module - shown here for layout only.</p>

            {{-- Payment method: manual bank transfer is live (Phase 3.1); Xendit methods arrive in 3.2 --}}
            <h2 class="checkout-heading">Payment method</h2>
            <div class="checkout-options" aria-label="Payment method">
                <label class="checkout-option checkout-option--selected">
                    <input type="radio" name="payment" checked disabled>
                    <span class="checkout-option-body">
                        <span class="checkout-option-title">Bank transfer</span>
                        <span class="checkout-option-sub">Bank details + a proof-of-payment upload shown after you place the order</span>
                    </span>
                </label>
                <label class="checkout-option checkout-option--disabled">
                    <input type="radio" name="payment" disabled>
                    <span class="checkout-option-body">
                        <span class="checkout-option-title">Virtual Account / QRIS / E-wallet / Card</span>
                        <span class="checkout-option-sub">Available once Xendit is activated</span>
                    </span>
                </label>
            </div>
        </div>

        <div class="checkout-summary">
            <h2 class="checkout-heading">Order summary</h2>
            <div class="checkout-summary-row">
                <span>Subtotal ({{ $totals['itemCount'] }} items)</span>
                <span>{{ $totals['formatted'] }}</span>
            </div>
            @if ($totals['discount'] > 0)
                <div class="checkout-summary-row checkout-summary-row--discount">
                    <span>Discount{{ $totals['discountLabel'] ? ' - '.$totals['discountLabel'] : '' }}</span>
                    <span>- {{ $totals['discountFormatted'] }}</span>
                </div>
            @endif
            <div class="checkout-summary-row checkout-summary-row--total">
                <span>Total</span>
                <span>{{ $totals['totalFormatted'] }}</span>
            </div>

            {{-- Coupon --}}
            <div class="checkout-coupon">
                @if ($totals['couponCode'])
                    <div class="cart-coupon-applied">
                        <span>Coupon <strong>{{ $totals['couponCode'] }}</strong></span>
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

            <p class="checkout-note">Shipping is arranged after checkout. Bank transfer details and a proof-of-payment upload appear on your order confirmation page.</p>
            <button type="submit" class="btn-add-to-cart">Place order</button>
        </div>
    </form>
</div>
