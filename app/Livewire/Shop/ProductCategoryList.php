<?php

namespace App\Livewire\Shop;

use App\Enums\Permission;
use App\Models\ProductCategory;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Product Categories')]
class ProductCategoryList extends Component
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

        $category = ProductCategory::findOrFail($id);
        // Detach products before deleting (pivot rows auto-delete via FK cascade, but be explicit).
        $category->products()->detach();
        $category->delete();

        $this->confirmingDelete = null;
        session()->flash('success', 'Category deleted.');
    }

    public function render()
    {
        return view('livewire.shop.product-category-list', [
            'categories' => ProductCategory::with('parent')->orderBy('name')->get(),
        ]);
    }
}
