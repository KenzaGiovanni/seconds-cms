<div>
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('admin.shop.products.index') }}"
           wire:navigate
           class="text-sm text-muted transition hover:text-ink">&larr; Products</a>
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink">
            {{ $editing ? 'Edit Product' : 'New Product' }}
        </h1>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-[var(--radius-btn)] border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Main column --}}
        <div class="space-y-5 lg:col-span-2">
            {{-- Name --}}
            <div>
                <label class="mb-1 block font-display text-sm font-medium text-ink">Name</label>
                <input wire:model.live="name" type="text" placeholder="Product name"
                       class="w-full rounded-[var(--radius-btn)] border border-line bg-bg px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Slug --}}
            <div>
                <label class="mb-1 block font-display text-sm font-medium text-ink">
                    Slug <span class="ml-1 font-normal text-muted">(URL path)</span>
                </label>
                <div class="flex items-center rounded-[var(--radius-btn)] border border-line bg-bg focus-within:border-accent focus-within:ring-1 focus-within:ring-accent">
                    <span class="border-r border-line px-3 py-2 text-sm text-muted">/shop/</span>
                    <input wire:model.live="slug" type="text" placeholder="product-slug"
                           class="flex-1 bg-transparent px-3 py-2 font-mono text-sm text-ink placeholder:text-muted focus:outline-none">
                </div>
                @error('slug') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Description --}}
            <div>
                <label class="mb-1 block font-display text-sm font-medium text-ink">Description</label>
                <textarea wire:model="description" rows="4" placeholder="Short product description..."
                          class="w-full rounded-[var(--radius-btn)] border border-line bg-bg px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Rich content blocks (for detailed product description) --}}
            @include('livewire.content.partials.block-editor')

            {{-- Simple product: price / sku / stock --}}
            @if ($type === 'simple')
                <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-4 space-y-4">
                    <h2 class="font-display text-sm font-semibold text-ink">Pricing & Inventory</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-muted">Price (IDR)</label>
                            <input wire:model="price" type="number" min="0" placeholder="0"
                                   class="w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                            @error('price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-muted">SKU</label>
                            <input wire:model="sku" type="text" placeholder="Optional"
                                   class="w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                            @error('sku') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-muted">Stock quantity</label>
                        <input wire:model="stock" type="number" min="0" placeholder="Leave blank if not tracking"
                               class="w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                        @error('stock') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endif

            {{-- Variable product: variant editor --}}
            @if ($type === 'variable')
                <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-4 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="font-display text-sm font-semibold text-ink">Variants</h2>
                        <button type="button" wire:click="addVariant"
                                class="rounded-[var(--radius-btn)] border border-line px-3 py-1.5 font-display text-xs font-medium text-ink transition hover:bg-soft">
                            + Add variant
                        </button>
                    </div>

                    @if (empty($variants))
                        <p class="text-xs text-muted">No variants yet. Add at least one.</p>
                    @else
                        <div class="space-y-4">
                            @foreach ($variants as $i => $variant)
                                <div wire:key="variant-{{ $i }}" class="rounded-[var(--radius-btn)] border border-line bg-soft p-3 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="font-display text-xs font-medium text-muted">Variant {{ $i + 1 }}</span>
                                        <button type="button" wire:click="removeVariant({{ $i }})"
                                                class="text-xs text-red-500 hover:text-red-700">Remove</button>
                                    </div>

                                    {{-- Option fields (up to 2) --}}
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-muted">Option 1 name</label>
                                            <input wire:model="variants.{{ $i }}.opt1k" type="text" placeholder="e.g. Size"
                                                   class="w-full rounded-[var(--radius-btn)] border border-line bg-bg px-2 py-1.5 text-xs text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-muted">Option 1 value</label>
                                            <input wire:model="variants.{{ $i }}.opt1v" type="text" placeholder="e.g. L"
                                                   class="w-full rounded-[var(--radius-btn)] border border-line bg-bg px-2 py-1.5 text-xs text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-muted">Option 2 name</label>
                                            <input wire:model="variants.{{ $i }}.opt2k" type="text" placeholder="e.g. Color (optional)"
                                                   class="w-full rounded-[var(--radius-btn)] border border-line bg-bg px-2 py-1.5 text-xs text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-muted">Option 2 value</label>
                                            <input wire:model="variants.{{ $i }}.opt2v" type="text" placeholder="e.g. Red (optional)"
                                                   class="w-full rounded-[var(--radius-btn)] border border-line bg-bg px-2 py-1.5 text-xs text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                                        </div>
                                    </div>

                                    {{-- Variant price / sku / stock --}}
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-muted">Price (IDR)</label>
                                            <input wire:model="variants.{{ $i }}.price" type="number" min="0" placeholder="0"
                                                   class="w-full rounded-[var(--radius-btn)] border border-line bg-bg px-2 py-1.5 text-xs text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                                            @error("variants.$i.price") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-muted">SKU</label>
                                            <input wire:model="variants.{{ $i }}.sku" type="text" placeholder="Optional"
                                                   class="w-full rounded-[var(--radius-btn)] border border-line bg-bg px-2 py-1.5 text-xs text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-muted">Stock</label>
                                            <input wire:model="variants.{{ $i }}.stock" type="number" min="0" placeholder="-"
                                                   class="w-full rounded-[var(--radius-btn)] border border-line bg-bg px-2 py-1.5 text-xs text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">
            {{-- Publish --}}
            <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-4 space-y-4">
                <h2 class="font-display text-sm font-semibold text-ink">Publish</h2>
                <div>
                    <label class="mb-1 block text-xs font-medium text-muted">Status</label>
                    <select wire:model.live="status"
                            class="w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                        @foreach ($statuses as $s)
                            <option value="{{ $s->value }}">{{ $s->label() }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit"
                        class="w-full rounded-[var(--radius-btn)] bg-accent py-2 font-display text-sm font-medium text-white transition hover:bg-accent/90">
                    {{ $editing ? 'Update product' : 'Save product' }}
                </button>
            </div>

            {{-- Product type --}}
            <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-4 space-y-3">
                <h2 class="font-display text-sm font-semibold text-ink">Type</h2>
                <select wire:model.live="type"
                        class="w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                    @foreach ($types as $t)
                        <option value="{{ $t->value }}">{{ ucfirst($t->value) }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-muted">
                    @if ($type === 'simple')
                        Single product with one price and SKU.
                    @else
                        Multiple variants (e.g. sizes, colors) each with their own price and stock.
                    @endif
                </p>
                @error('type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Stock policy --}}
            <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-4 space-y-3">
                <h2 class="font-display text-sm font-semibold text-ink">Stock policy</h2>
                <select wire:model="stockPolicy"
                        class="w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                    @foreach ($stockPolicies as $p)
                        <option value="{{ $p->value }}">{{ $p->label() }}</option>
                    @endforeach
                </select>
                @error('stockPolicy') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Featured image --}}
            <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-4 space-y-3">
                <h2 class="font-display text-sm font-semibold text-ink">Featured image</h2>
                @if ($featuredImageUrl)
                    <img src="{{ $featuredImageUrl }}" alt="" class="w-full rounded object-cover" style="max-height:160px">
                    <button type="button" wire:click="removeFeaturedImage" class="text-xs text-red-500 hover:text-red-700">Remove image</button>
                @else
                    <p class="text-xs text-muted">No image selected.</p>
                @endif
                <livewire:media.media-picker />
            </div>

            {{-- Categories --}}
            <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-4 space-y-3">
                <h2 class="font-display text-sm font-semibold text-ink">Categories</h2>
                @if ($categories->isEmpty())
                    <p class="text-xs text-muted">No categories yet.
                        <a href="{{ route('admin.shop.categories.create') }}" wire:navigate class="text-accent underline">Create one.</a>
                    </p>
                @else
                    <div class="max-h-48 space-y-1.5 overflow-y-auto">
                        @foreach ($categories as $cat)
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="checkbox" wire:model="selectedCategories"
                                       value="{{ $cat->id }}"
                                       class="rounded border-line text-accent focus:ring-accent">
                                <span class="text-sm text-ink">{{ $cat->name }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </form>
</div>
