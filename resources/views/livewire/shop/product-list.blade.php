<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink">Products</h1>
        <a href="{{ route('admin.shop.products.create') }}"
           wire:navigate
           class="rounded-[var(--radius-btn)] bg-accent px-4 py-2 font-display text-sm font-medium text-white transition hover:bg-accent/90">
            New product
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-[var(--radius-btn)] border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($products->isEmpty())
        <div class="rounded-[var(--radius-btn)] border border-line bg-bg px-6 py-12 text-center text-sm text-muted">
            No products yet. <a href="{{ route('admin.shop.products.create') }}" wire:navigate class="text-accent underline">Create your first product.</a>
        </div>
    @else
        <div class="overflow-hidden rounded-[var(--radius-btn)] border border-line bg-bg">
            <table class="w-full text-sm">
                <thead class="border-b border-line bg-soft">
                    <tr>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Name</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Type</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Price</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Stock</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($products as $product)
                        <tr wire:key="{{ $product->id }}">
                            <td class="px-4 py-3">
                                <div class="font-medium text-ink">{{ $product->name }}</div>
                                @if ($product->categories->isNotEmpty())
                                    <div class="mt-0.5 text-xs text-muted">{{ $product->categories->pluck('name')->implode(', ') }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-muted capitalize">{{ $product->type->value }}</td>
                            <td class="px-4 py-3 text-ink">
                                @if ($product->isSimple())
                                    {{ $product->formattedPrice() }}
                                @else
                                    {{ $product->variants->count() }} variants
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($product->stock_policy->value === 'none')
                                    <span class="text-xs text-muted">Not tracked</span>
                                @elseif ($product->isSimple())
                                    @php $stock = $product->stock ?? 0; @endphp
                                    <span @class([
                                        'text-sm font-medium',
                                        'text-red-600' => $stock <= 0,
                                        'text-yellow-600' => $stock > 0 && $stock <= $lowStockThreshold,
                                        'text-ink' => $stock > $lowStockThreshold,
                                    ])>{{ $stock }}</span>
                                    @if ($stock <= 0)
                                        <span class="ml-1 text-xs text-red-600">out of stock</span>
                                    @elseif ($stock <= $lowStockThreshold)
                                        <span class="ml-1 text-xs text-yellow-600">low</span>
                                    @endif
                                @else
                                    <span class="text-sm text-ink">{{ $product->variants->sum('stock') }}</span>
                                    <span class="ml-1 text-xs text-muted">across {{ $product->variants->count() }} variants</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-green-100 text-green-800' => $product->status->value === 'published',
                                    'bg-gray-100 text-gray-600' => $product->status->value === 'draft',
                                ])>
                                    {{ $product->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.shop.products.edit', $product->id) }}"
                                       wire:navigate
                                       class="rounded px-2 py-1 text-xs font-medium text-muted transition hover:text-ink">
                                        Edit
                                    </a>

                                    @if ($confirmingDelete === $product->id)
                                        <span class="text-xs text-muted">Sure?</span>
                                        <button wire:click="delete({{ $product->id }})"
                                                class="rounded px-2 py-1 text-xs font-medium text-red-600 transition hover:text-red-800">
                                            Yes, delete
                                        </button>
                                        <button wire:click="cancelDelete"
                                                class="rounded px-2 py-1 text-xs font-medium text-muted transition hover:text-ink">
                                            Cancel
                                        </button>
                                    @else
                                        <button wire:click="confirmDelete({{ $product->id }})"
                                                class="rounded px-2 py-1 text-xs font-medium text-muted transition hover:text-red-600">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
