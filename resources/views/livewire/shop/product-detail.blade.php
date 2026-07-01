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

    {{-- Add to cart (wired in Phase 2.3; placeholder for now) --}}
    <button type="button"
            class="btn-add-to-cart"
            @if(! $inStock) disabled @endif>
        Add to cart
    </button>
</div>
