<?php

namespace App\Livewire\Shop;

use App\Enums\Permission;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\StockPolicy;
use App\Livewire\Concerns\WithBlockEditor;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.admin')]
class ProductForm extends Component
{
    use WithBlockEditor;

    public ?int $productId = null;

    public string $name = '';

    public string $slug = '';

    public string $type = 'simple';

    public string $status = 'draft';

    public string $description = '';

    public string $sku = '';

    public string $price = '';

    public string $stock = '';

    public string $stockPolicy = 'deny';

    public array $selectedCategories = [];

    public ?int $featuredImageId = null;

    public ?string $featuredImageUrl = null;

    public bool $slugManuallyEdited = false;

    /**
     * Variant rows for variable products.
     * Each: ['id'=>null, 'sku'=>'', 'price'=>'', 'stock'=>'', 'opt1k'=>'', 'opt1v'=>'', 'opt2k'=>'', 'opt2v'=>'']
     *
     * @var array<int, array<string, mixed>>
     */
    public array $variants = [];

    public function mount(?int $id = null): void
    {
        abort_unless(auth()->user()->can(Permission::ProductsManage->value), 403);

        if ($id) {
            $product = Product::with(['categories', 'variants'])->findOrFail($id);
            $this->productId = $product->id;
            $this->name = $product->name;
            $this->slug = $product->slug;
            $this->type = $product->type->value;
            $this->status = $product->status->value;
            $this->description = $product->description ?? '';
            $this->sku = $product->sku ?? '';
            $this->price = $product->price !== null ? (string) $product->price : '';
            $this->stock = $product->stock !== null ? (string) $product->stock : '';
            $this->stockPolicy = $product->stock_policy->value;
            $this->selectedCategories = $product->categories->pluck('id')->map(fn ($v) => (string) $v)->toArray();
            $this->featuredImageId = $product->featured_image_id;
            $this->featuredImageUrl = $product->featuredImage?->url();
            $this->blocks = $product->blocks ?? [];
            $this->slugManuallyEdited = true;

            // Load variants
            foreach ($product->variants as $v) {
                $opts = $v->options ?? [];
                $keys = array_keys($opts);
                $vals = array_values($opts);
                $this->variants[] = [
                    'id' => $v->id,
                    'sku' => $v->sku ?? '',
                    'price' => (string) $v->price,
                    'stock' => (string) $v->stock,
                    'opt1k' => $keys[0] ?? '',
                    'opt1v' => $vals[0] ?? '',
                    'opt2k' => $keys[1] ?? '',
                    'opt2v' => $vals[1] ?? '',
                ];
            }
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

    #[On('media-selected')]
    public function onMediaSelected(int $id, string $url): void
    {
        if ($this->applyBlockMedia($id, $url)) {
            return;
        }

        $this->featuredImageId = $id;
        $this->featuredImageUrl = $url;
    }

    public function removeFeaturedImage(): void
    {
        $this->featuredImageId = null;
        $this->featuredImageUrl = null;
    }

    public function addVariant(): void
    {
        $this->variants[] = [
            'id' => null,
            'sku' => '',
            'price' => '',
            'stock' => '',
            'opt1k' => '',
            'opt1v' => '',
            'opt2k' => '',
            'opt2v' => '',
        ];
    }

    public function removeVariant(int $index): void
    {
        array_splice($this->variants, $index, 1);
        $this->variants = array_values($this->variants);
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can(Permission::ProductsManage->value), 403);

        $this->slug = Str::slug($this->slug) ?: Str::slug($this->name);

        $rules = [
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9][a-z0-9\-]*$/',
                function ($attribute, $value, $fail) {
                    $exists = Product::where('slug', $value)
                        ->when($this->productId, fn ($q) => $q->where('id', '!=', $this->productId))
                        ->exists();
                    if ($exists) {
                        $fail('This slug is already taken.');
                    }
                },
            ],
            'type' => 'required|in:simple,variable',
            'status' => 'required|in:draft,published',
            'description' => 'nullable|string',
            'stockPolicy' => 'required|in:none,deny,backorder',
            'selectedCategories' => 'nullable|array',
            'selectedCategories.*' => 'integer|exists:product_categories,id',
            'blocks' => 'nullable|array',
        ];

        if ($this->type === 'simple') {
            $rules['sku'] = 'nullable|string|max:100';
            $rules['price'] = 'required|integer|min:0';
            $rules['stock'] = 'nullable|integer|min:0';
        } else {
            $rules['variants'] = 'nullable|array';
            $rules['variants.*.sku'] = 'nullable|string|max:100';
            $rules['variants.*.price'] = 'required|integer|min:0';
            $rules['variants.*.stock'] = 'nullable|integer|min:0';
        }

        $data = $this->validate($rules);

        $payload = [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'type' => $data['type'],
            'status' => $data['status'],
            'description' => $data['description'] ?? null,
            'stock_policy' => $data['stockPolicy'],
            'blocks' => count($this->blocks) ? $this->blocks : null,
            'featured_image_id' => $this->featuredImageId,
        ];

        if ($this->type === 'simple') {
            $payload['sku'] = $data['sku'] ?: null;
            $payload['price'] = (int) $data['price'];
            $payload['stock'] = isset($data['stock']) && $data['stock'] !== '' ? (int) $data['stock'] : null;
        } else {
            $payload['sku'] = null;
            $payload['price'] = null;
            $payload['stock'] = null;
        }

        if ($this->productId) {
            $product = Product::findOrFail($this->productId);
            $product->update($payload);
            session()->flash('success', 'Product updated.');
        } else {
            $product = Product::create($payload);
            session()->flash('success', 'Product created.');
        }

        // Sync categories
        $product->categories()->sync(array_map('intval', $data['selectedCategories'] ?? []));

        // Sync variants for variable products
        if ($this->type === 'variable') {
            $existingIds = collect($this->variants)->pluck('id')->filter()->toArray();

            // Delete removed variants
            if ($this->productId) {
                ProductVariant::where('product_id', $product->id)
                    ->whereNotIn('id', $existingIds)
                    ->delete();
            }

            foreach ($this->variants as $row) {
                $options = [];
                if (($row['opt1k'] ?? '') !== '' && ($row['opt1v'] ?? '') !== '') {
                    $options[$row['opt1k']] = $row['opt1v'];
                }
                if (($row['opt2k'] ?? '') !== '' && ($row['opt2v'] ?? '') !== '') {
                    $options[$row['opt2k']] = $row['opt2v'];
                }

                $variantData = [
                    'product_id' => $product->id,
                    'sku' => $row['sku'] ?: null,
                    'price' => (int) ($row['price'] ?? 0),
                    'stock' => $row['stock'] !== '' ? (int) $row['stock'] : null,
                    'options' => $options ?: null,
                ];

                if ($row['id']) {
                    ProductVariant::where('id', $row['id'])->update($variantData);
                } else {
                    ProductVariant::create($variantData);
                }
            }
        } else {
            // Switching from variable to simple: remove all variants
            $product->variants()->delete();
        }

        $this->redirect(route('admin.shop.products.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.shop.product-form', [
            'statuses' => ProductStatus::cases(),
            'types' => ProductType::cases(),
            'stockPolicies' => StockPolicy::cases(),
            'categories' => ProductCategory::orderBy('name')->get(),
            'editing' => $this->productId !== null,
        ]);
    }

    public function title(): string
    {
        return $this->productId ? 'Edit Product' : 'New Product';
    }
}
