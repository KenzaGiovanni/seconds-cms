<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="font-display text-2xl font-bold text-ink">Menus</h1>
        <a href="{{ route('admin.menus.create') }}"
           wire:navigate
           class="rounded-[var(--radius-btn)] bg-accent px-4 py-2 font-display text-sm font-medium text-white transition hover:bg-accent/90">
            New menu
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-[var(--radius-btn)] border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($menus->isEmpty())
        <div class="rounded-[var(--radius-btn)] border border-line bg-bg px-6 py-12 text-center text-sm text-muted">
            No menus yet. <a href="{{ route('admin.menus.create') }}" wire:navigate class="text-accent underline">Create your first menu.</a>
        </div>
    @else
        <div class="overflow-hidden rounded-[var(--radius-btn)] border border-line bg-bg">
            <table class="w-full text-sm">
                <thead class="border-b border-line bg-soft">
                    <tr>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Name</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Location</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Items</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($menus as $menu)
                        <tr wire:key="{{ $menu->id }}">
                            <td class="px-4 py-3 font-medium text-ink">{{ $menu->name }}</td>
                            <td class="px-4 py-3 text-muted">{{ $menu->location ?? '-' }}</td>
                            <td class="px-4 py-3 text-muted">{{ $menu->items_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.menus.edit', $menu->id) }}"
                                       wire:navigate
                                       class="rounded px-2 py-1 text-xs font-medium text-muted transition hover:text-ink">
                                        Edit
                                    </a>
                                    @if ($confirmingDelete === $menu->id)
                                        <span class="text-xs text-muted">Sure?</span>
                                        <button wire:click="delete({{ $menu->id }})"
                                                class="rounded px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800">
                                            Yes, delete
                                        </button>
                                        <button wire:click="cancelDelete"
                                                class="rounded px-2 py-1 text-xs font-medium text-muted hover:text-ink">
                                            Cancel
                                        </button>
                                    @else
                                        <button wire:click="confirmDelete({{ $menu->id }})"
                                                class="rounded px-2 py-1 text-xs font-medium text-muted hover:text-red-600">
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
