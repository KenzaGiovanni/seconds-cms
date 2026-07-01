<?php

namespace App\Livewire\Shop;

use App\Enums\Permission;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Products')]
class ProductList extends Component
{
    public ?int $confirmingDelete = null;

    /** Pending stock edits keyed by product id, bound to the inline stock input. */
    public array $stockEdits = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can(Permission::ProductsManage->value), 403);
    }

    /** Manual stock adjustment for a simple, stock-tracking product - bypasses the full edit form. */
    public function adjustStock(int $productId): void
    {
        abort_unless(auth()->user()->can(Permission::ProductsManage->value), 403);

        $product = Product::findOrFail($productId);

        if (! $product->isSimple() || ! $product->stock_policy->tracksStock()) {
            return;
        }

        $newStock = (int) ($this->stockEdits[$productId] ?? $product->stock);
        $product->update(['stock' => max(0, $newStock)]);

        unset($this->stockEdits[$productId]);
        session()->flash('success', 'Stock updated.');
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDelete = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = null;
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can(Permission::ProductsManage->value), 403);

        $product = Product::findOrFail($id);
        $product->variants()->delete();
        $product->categories()->detach();
        $product->delete();

        $this->confirmingDelete = null;
        session()->flash('success', 'Product deleted.');
    }

    public function render()
    {
        return view('livewire.shop.product-list', [
            'products' => Product::with(['categories', 'variants'])->latest()->get(),
            'lowStockThreshold' => config('seconds.low_stock_threshold'),
        ]);
    }
}
