<div class="product-detail-widget">
    {{-- Price --}}
    <div class="product-price">{{ $displayPrice }}</div>

    {{-- Variant selector --}}
    @if ($product->isVariable() && $product->variants->isNotEmpty())
        <div class="product-variants">
            @foreach ($product->variants as $variant)
                <button type="button"
                        wire:click="selectVariant({{ $variant->id }})"
                        @class([
                            'variant-btn',
                            'variant-btn--selected' => $selectedVariantId === $variant->id,
                        ])>
                    {{ $variant->label() ?: 'Variant ' . $loop->iteration }}
                </button>
            @endforeach
        </div>
    @endif

    {{-- Stock indicator --}}
    @if (! $inStock)
        <p class="stock-badge stock-badge--out">Out of stock</p>
    @endif

    @if ($addedMessage)
        <p class="cart-feedback cart-feedback--success">{{ $addedMessage }}</p>
    @endif
    @if ($errorMessage)
        <p class="cart-feedback cart-feedback--error">{{ $errorMessage }}</p>
    @endif

    <div class="add-to-cart-row">
        <input type="number" wire:model="quantity" min="1" class="qty-input" @if(! $inStock) disabled @endif>
        <button type="button"
                wire:click="addToCart"
                class="btn-add-to-cart"
                @if(! $inStock) disabled @endif>
            Add to cart
        </button>
    </div>
</div>
