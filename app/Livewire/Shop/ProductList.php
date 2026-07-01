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

    public function mount(): void
    {
        abort_unless(auth()->user()->can(Permission::ProductsManage->value), 403);
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
        ]);
    }
}
