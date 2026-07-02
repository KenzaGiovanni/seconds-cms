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
                    <label>Province</label>
                    <select wire:model.live="provinceCode">
                        <option value="">Select province</option>
                        @foreach ($this->provinceOptions() as $province)
                            <option value="{{ $province->code }}">{{ $province->name }}</option>
                        @endforeach
                    </select>
                    @error('provinceCode') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="checkout-field">
                    <label>City / Regency</label>
                    <select wire:model.live="regencyCode" @disabled(! $provinceCode)>
                        <option value="">Select city / regency</option>
                        @foreach ($this->regencyOptions() as $regency)
                            <option value="{{ $regency->code }}">{{ $regency->name }}</option>
                        @endforeach
                    </select>
                    @error('regencyCode') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="checkout-field-row">
                <div class="checkout-field">
                    <label>District</label>
                    <select wire:model.live="districtCode" @disabled(! $regencyCode)>
                        <option value="">Select district</option>
                        @foreach ($this->districtOptions() as $district)
                            <option value="{{ $district->code }}">{{ $district->name }}</option>
                        @endforeach
                    </select>
                    @error('districtCode') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="checkout-field">
                    <label>Postal code</label>
                    @php($postalOptions = $this->postalCodeOptions())
                    @if ($postalOptions->isNotEmpty())
                        <select wire:model="postalCode">
                            <option value="">Select postal code</option>
                            @foreach ($postalOptions as $option)
                                <option value="{{ $option->postal_code }}">{{ $option->postal_code }} - {{ $option->urban }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" wire:model="postalCode" placeholder="{{ $districtCode ? 'Not in our postal code data - enter manually' : '' }}">
                    @endif
                    @error('postalCode') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="checkout-field">
                <label>Order notes <span class="optional">(optional)</span></label>
                <textarea wire:model="notes" rows="3"></textarea>
            </div>

            {{-- Delivery method: live options from ShipmentService (Phase 4.1) --}}
            <h2 class="checkout-heading">Delivery method</h2>
            <div class="checkout-options" aria-label="Delivery method">
                @foreach ($rates as $rate)
                    <label @class(['checkout-option', 'checkout-option--selected' => $deliveryChoice === $rate->id()])>
                        <input type="radio" name="delivery" value="{{ $rate->id() }}" wire:model="deliveryChoice">
                        <span class="checkout-option-body">
                            <span class="checkout-option-title">{{ $rate->serviceName }}</span>
                            @if ($rate->etaText)
                                <span class="checkout-option-sub">{{ $rate->etaText }}</span>
                            @endif
                        </span>
                        <span class="checkout-option-price">{{ $rate->formattedCost() }}</span>
                    </label>
                @endforeach
            </div>

            {{-- Payment method: reflects the active provider + its enabled methods (Phase 3.4) --}}
            <h2 class="checkout-heading">Payment method</h2>
            <div class="checkout-options" aria-label="Payment method">
                @foreach ($paymentMethods as $method)
                    <label @class(['checkout-option', 'checkout-option--selected' => $loop->first])>
                        <input type="radio" name="payment" @checked($loop->first) disabled>
                        <span class="checkout-option-body">
                            <span class="checkout-option-title">{{ $method->label() }}</span>
                            <span class="checkout-option-sub">
                                @if ($method->value === 'bank_transfer')
                                    Bank details + a proof-of-payment upload shown after you place the order
                                @else
                                    Pay via the hosted Xendit checkout page after placing your order
                                @endif
                            </span>
                        </span>
                    </label>
                @endforeach

                @if ($paymentProvider->value === 'manual')
                    <label class="checkout-option checkout-option--disabled">
                        <input type="radio" name="payment" disabled>
                        <span class="checkout-option-body">
                            <span class="checkout-option-title">Virtual Account / QRIS / E-wallet / Card</span>
                            <span class="checkout-option-sub">Available once Xendit is activated</span>
                        </span>
                    </label>
                @endif
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
            @php($chosenRate = collect($rates)->first(fn ($rate) => $rate->id() === $deliveryChoice))
            <div class="checkout-summary-row">
                <span>Shipping</span>
                <span>{{ $chosenRate?->formattedCost() ?? '-' }}</span>
            </div>
            <div class="checkout-summary-row checkout-summary-row--total">
                <span>Total</span>
                <span>{{ \App\Support\Money::format($totals['total'] + ($chosenRate?->cost ?? 0), $totals['currency']) }}</span>
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

            <p class="checkout-note">Bank transfer details and a proof-of-payment upload appear on your order confirmation page.</p>
            <button type="submit" class="btn-add-to-cart">Place order</button>
        </div>
    </form>
</div>
