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
        </div>

        <div class="checkout-summary">
            <h2 class="checkout-heading">Order summary</h2>
            <div class="checkout-summary-row">
                <span>Subtotal ({{ $totals['itemCount'] }} items)</span>
                <span>{{ $totals['formatted'] }}</span>
            </div>
            <div class="checkout-summary-row checkout-summary-row--total">
                <span>Total</span>
                <span>{{ $totals['formatted'] }}</span>
            </div>
            <p class="checkout-note">Shipping and payment are arranged after checkout (payment provider integration coming soon).</p>
            <button type="submit" class="btn-add-to-cart">Place order</button>
        </div>
    </form>
</div>
