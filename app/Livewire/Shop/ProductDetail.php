<?php

namespace App\Livewire\Shop;

use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Component;

/**
 * Handles variant selection on the storefront product detail page.
 * Embedded inside the theme template via @livewire('shop.product-detail', ...).
 */
class ProductDetail extends Component
{
    public int $productId;

    public ?int $selectedVariantId = null;

    public ?Product $product = null;

    public function mount(int $productId): void
    {
        $this->productId = $productId;
        $this->product = Product::with('variants')->findOrFail($productId);

        // Default to first variant for variable products.
        if ($this->product->isVariable() && $this->product->variants->isNotEmpty()) {
            $this->selectedVariantId = $this->product->variants->first()->id;
        }
    }

    public function selectVariant(int $variantId): void
    {
        $variant = $this->product->variants->firstWhere('id', $variantId);
        if ($variant) {
            $this->selectedVariantId = $variantId;
        }
    }

    public function getSelectedVariant(): ?ProductVariant
    {
        if (! $this->selectedVariantId) {
            return null;
        }

        return $this->product->variants->firstWhere('id', $this->selectedVariantId);
    }

    public function getDisplayPrice(): string
    {
        if ($this->product->isSimple()) {
            return $this->product->formattedPrice();
        }

        $variant = $this->getSelectedVariant();

        return $variant ? $variant->formattedPrice() : '-';
    }

    public function isInStock(): bool
    {
        if ($this->product->stock_policy->value === 'none') {
            return true;
        }

        if ($this->product->stock_policy->value === 'backorder') {
            return true;
        }

        if ($this->product->isSimple()) {
            return ($this->product->stock ?? 0) > 0;
        }

        $variant = $this->getSelectedVariant();

        return $variant ? ($variant->stock ?? 0) > 0 : false;
    }

    public function render()
    {
        return view('livewire.shop.product-detail', [
            'selectedVariant' => $this->getSelectedVariant(),
            'displayPrice' => $this->getDisplayPrice(),
            'inStock' => $this->isInStock(),
        ]);
    }
}
