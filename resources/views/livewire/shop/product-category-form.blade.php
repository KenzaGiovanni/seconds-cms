<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink">{{ $editing ? 'Edit Category' : 'New Category' }}</h1>
        <a href="{{ route('admin.shop.categories.index') }}"
           wire:navigate
           class="rounded-[var(--radius-btn)] border border-line px-4 py-2 font-display text-sm font-medium text-muted transition hover:text-ink">
            Cancel
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-[var(--radius-btn)] border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="max-w-xl space-y-5">
        <div>
            <label class="mb-1.5 block font-display text-sm font-medium text-ink">Name</label>
            <input wire:model.live="name" type="text"
                   class="w-full rounded-[var(--radius-btn)] border border-line bg-bg px-3 py-2 text-sm text-ink outline-none focus:border-accent focus:ring-1 focus:ring-accent">
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1.5 block font-display text-sm font-medium text-ink">Slug</label>
            <input wire:model="slug" type="text"
                   class="w-full rounded-[var(--radius-btn)] border border-line bg-bg px-3 py-2 font-mono text-sm text-ink outline-none focus:border-accent focus:ring-1 focus:ring-accent">
            @error('slug') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1.5 block font-display text-sm font-medium text-ink">Parent category</label>
            <select wire:model="parentId"
                    class="w-full rounded-[var(--radius-btn)] border border-line bg-bg px-3 py-2 text-sm text-ink outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                <option value="">None (top-level)</option>
                @foreach ($parents as $parent)
                    <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                @endforeach
            </select>
            @error('parentId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1.5 block font-display text-sm font-medium text-ink">Description</label>
            <textarea wire:model="description" rows="3"
                      class="w-full rounded-[var(--radius-btn)] border border-line bg-bg px-3 py-2 text-sm text-ink outline-none focus:border-accent focus:ring-1 focus:ring-accent"></textarea>
            @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit"
                    class="rounded-[var(--radius-btn)] bg-accent px-5 py-2 font-display text-sm font-medium text-white transition hover:bg-accent/90">
                {{ $editing ? 'Update category' : 'Create category' }}
            </button>
        </div>
    </form>
</div>
