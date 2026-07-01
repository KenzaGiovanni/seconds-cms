<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink">Product Categories</h1>
        <a href="{{ route('admin.shop.categories.create') }}"
           wire:navigate
           class="rounded-[var(--radius-btn)] bg-accent px-4 py-2 font-display text-sm font-medium text-white transition hover:bg-accent/90">
            New category
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-[var(--radius-btn)] border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($categories->isEmpty())
        <div class="rounded-[var(--radius-btn)] border border-line bg-bg px-6 py-12 text-center text-sm text-muted">
            No categories yet. <a href="{{ route('admin.shop.categories.create') }}" wire:navigate class="text-accent underline">Create your first category.</a>
        </div>
    @else
        <div class="overflow-hidden rounded-[var(--radius-btn)] border border-line bg-bg">
            <table class="w-full text-sm">
                <thead class="border-b border-line bg-soft">
                    <tr>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Name</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Slug</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Parent</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($categories as $category)
                        <tr wire:key="{{ $category->id }}">
                            <td class="px-4 py-3 font-medium text-ink">{{ $category->name }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-muted">{{ $category->slug }}</td>
                            <td class="px-4 py-3 text-muted">{{ $category->parent?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.shop.categories.edit', $category->id) }}"
                                       wire:navigate
                                       class="rounded px-2 py-1 text-xs font-medium text-muted transition hover:text-ink">
                                        Edit
                                    </a>

                                    @if ($confirmingDelete === $category->id)
                                        <span class="text-xs text-muted">Sure?</span>
                                        <button wire:click="delete({{ $category->id }})"
                                                class="rounded px-2 py-1 text-xs font-medium text-red-600 transition hover:text-red-800">
                                            Yes, delete
                                        </button>
                                        <button wire:click="cancelDelete"
                                                class="rounded px-2 py-1 text-xs font-medium text-muted transition hover:text-ink">
                                            Cancel
                                        </button>
                                    @else
                                        <button wire:click="confirmDelete({{ $category->id }})"
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
