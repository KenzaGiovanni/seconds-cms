<?php

namespace App\Livewire\Shop;

use App\Enums\Permission;
use App\Models\ProductCategory;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class ProductCategoryForm extends Component
{
    public ?int $categoryId = null;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public ?int $parentId = null;

    public bool $slugManuallyEdited = false;

    public function mount(?int $id = null): void
    {
        abort_unless(auth()->user()->can(Permission::ProductsManage->value), 403);

        if ($id) {
            $cat = ProductCategory::findOrFail($id);
            $this->categoryId = $cat->id;
            $this->name = $cat->name;
            $this->slug = $cat->slug;
            $this->description = $cat->description ?? '';
            $this->parentId = $cat->parent_id;
            $this->slugManuallyEdited = true;
        }
    }

    public function updatedName(string $value): void
    {
        if (! $this->slugManuallyEdited) {
            $this->slug = Str::slug($value);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugManuallyEdited = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can(Permission::ProductsManage->value), 403);

        $this->slug = Str::slug($this->slug) ?: Str::slug($this->name);

        $data = $this->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9][a-z0-9\-]*$/',
                function ($attribute, $value, $fail) {
                    $exists = ProductCategory::where('slug', $value)
                        ->when($this->categoryId, fn ($q) => $q->where('id', '!=', $this->categoryId))
                        ->exists();
                    if ($exists) {
                        $fail('This slug is already taken.');
                    }
                },
            ],
            'description' => 'nullable|string',
            'parentId' => 'nullable|integer|exists:product_categories,id',
        ]);

        // Guard: a category cannot be its own parent.
        if ($this->categoryId && (int) $this->parentId === $this->categoryId) {
            $this->addError('parentId', 'A category cannot be its own parent.');

            return;
        }

        $payload = [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'parent_id' => $data['parentId'] ?: null,
        ];

        if ($this->categoryId) {
            ProductCategory::findOrFail($this->categoryId)->update($payload);
            session()->flash('success', 'Category updated.');
        } else {
            ProductCategory::create($payload);
            session()->flash('success', 'Category created.');
        }

        $this->redirect(route('admin.shop.categories.index'), navigate: true);
    }

    public function render()
    {
        $parents = ProductCategory::orderBy('name')
            ->when($this->categoryId, fn ($q) => $q->where('id', '!=', $this->categoryId))
            ->get();

        return view('livewire.shop.product-category-form', [
            'parents' => $parents,
            'editing' => $this->categoryId !== null,
        ]);
    }

    public function title(): string
    {
        return $this->categoryId ? 'Edit Category' : 'New Category';
    }
}
