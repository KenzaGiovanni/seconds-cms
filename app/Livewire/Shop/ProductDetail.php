<?php

namespace App\Livewire\Shop;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\CartManager;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Handles variant selection + add-to-cart on the storefront product detail
 * page. Embedded inside the theme template via @livewire('shop.product-detail', ...).
 */
class ProductDetail extends Component
{
    public int $productId;

    public ?int $selectedVariantId = null;

    public ?Product $product = null;

    public int $quantity = 1;

    public ?string $addedMessage = null;

    public ?string $errorMessage = null;

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
            $this->addedMessage = null;
            $this->errorMessage = null;
        }
    }

    #[On('cart-updated')]
    public function refreshStock(): void
    {
        $this->product->refresh();
        $this->product->load('variants');
    }

    public function addToCart(CartManager $cart): void
    {
        $this->addedMessage = null;
        $this->errorMessage = null;

        if (! $this->isInStock()) {
            $this->errorMessage = 'Out of stock.';

            return;
        }

        try {
            $cart->addItem($this->product, max(1, $this->quantity), $this->getSelectedVariant());
            $this->addedMessage = 'Added to cart.';
            $this->dispatch('cart-updated');
        } catch (\RuntimeException $e) {
            $this->errorMessage = $e->getMessage();
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
