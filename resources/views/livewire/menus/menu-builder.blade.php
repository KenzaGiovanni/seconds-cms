<div>
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('admin.menus.index') }}" wire:navigate class="text-sm text-muted hover:text-ink">&larr; Menus</a>
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink">{{ $editing ? 'Edit Menu' : 'New Menu' }}</h1>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-[var(--radius-btn)] border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left: menu settings + items --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Name + location --}}
            <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-5 space-y-4">
                <h2 class="font-display text-sm font-semibold text-ink">Menu settings</h2>
                <div>
                    <label class="mb-1 block text-xs font-medium text-muted">Name</label>
                    <input wire:model="name" type="text" placeholder="Main navigation"
                           class="w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent" />
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-muted">Theme location</label>
                    <select wire:model="location"
                            class="w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="">-- None --</option>
                        @foreach ($themeLocations as $loc)
                            <option value="{{ $loc }}">{{ $loc }}</option>
                        @endforeach
                    </select>
                    @error('location') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <button wire:click="saveMenu"
                        class="rounded-[var(--radius-btn)] bg-accent px-4 py-2 font-display text-sm font-medium text-white transition hover:bg-accent/90">
                    {{ $editing ? 'Save menu' : 'Create menu' }}
                </button>
            </div>

            @if ($editing && $menu)
                {{-- Current items --}}
                <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-5 space-y-3">
                    <h2 class="font-display text-sm font-semibold text-ink">Menu items</h2>
                    @if ($menu->rootItems->isEmpty())
                        <p class="text-sm text-muted">No items yet. Add some below.</p>
                    @else
                        <div class="divide-y divide-line">
                            @foreach ($menu->rootItems as $item)
                                <div wire:key="{{ $item->id }}" class="py-3">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="text-sm font-medium text-ink">{{ $item->label }}</span>
                                            <span class="ml-2 text-xs text-muted">{{ $item->resolvedUrl() }}</span>
                                        </div>
                                        <div class="flex gap-1">
                                            <button wire:click="moveUp({{ $item->id }})" title="Move up"
                                                    class="rounded px-1.5 py-1 text-xs text-muted hover:text-ink">&uarr;</button>
                                            <button wire:click="moveDown({{ $item->id }})" title="Move down"
                                                    class="rounded px-1.5 py-1 text-xs text-muted hover:text-ink">&darr;</button>
                                            <button wire:click="removeItem({{ $item->id }})"
                                                    class="rounded px-1.5 py-1 text-xs text-red-500 hover:text-red-700">Remove</button>
                                        </div>
                                    </div>
                                    @foreach ($item->children as $child)
                                        <div wire:key="{{ $child->id }}" class="ml-6 mt-2 flex items-center justify-between">
                                            <div>
                                                <span class="text-sm text-ink">&#x21B3; {{ $child->label }}</span>
                                                <span class="ml-2 text-xs text-muted">{{ $child->resolvedUrl() }}</span>
                                            </div>
                                            <div class="flex gap-1">
                                                <button wire:click="moveUp({{ $child->id }})"
                                                        class="rounded px-1.5 py-1 text-xs text-muted hover:text-ink">&uarr;</button>
                                                <button wire:click="moveDown({{ $child->id }})"
                                                        class="rounded px-1.5 py-1 text-xs text-muted hover:text-ink">&darr;</button>
                                                <button wire:click="removeItem({{ $child->id }})"
                                                        class="rounded px-1.5 py-1 text-xs text-red-500 hover:text-red-700">Remove</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Add item form --}}
                <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-5 space-y-4">
                    <h2 class="font-display text-sm font-semibold text-ink">Add item</h2>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-muted">Label</label>
                            <input wire:model="newLabel" type="text" placeholder="Home"
                                   class="w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent" />
                            @error('newLabel') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-muted">Parent (optional)</label>
                            <select wire:model="newParentId"
                                    class="w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                                <option value="">-- Top level --</option>
                                @foreach ($menu->rootItems as $item)
                                    <option value="{{ $item->id }}">{{ $item->label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-muted">Link type</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 text-sm text-ink">
                                <input type="radio" wire:model.live="newLinkType" value="url" class="text-accent" /> Custom URL
                            </label>
                            <label class="flex items-center gap-2 text-sm text-ink">
                                <input type="radio" wire:model.live="newLinkType" value="content" class="text-accent" /> Content
                            </label>
                        </div>
                    </div>
                    @if ($newLinkType === 'url')
                        <div>
                            <label class="mb-1 block text-xs font-medium text-muted">URL</label>
                            <input wire:model="newUrl" type="text" placeholder="https://example.com"
                                   class="w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent" />
                        </div>
                    @else
                        <div>
                            <label class="mb-1 block text-xs font-medium text-muted">Content</label>
                            <select wire:model="newContentId"
                                    class="w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                                <option value="">-- Select content --</option>
                                @foreach ($allContent as $c)
                                    <option value="{{ $c->id }}">[{{ ucfirst($c->type) }}] {{ $c->title }}</option>
                                @endforeach
                            </select>
                            @error('newContentId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif
                    <button wire:click="addItem"
                            class="rounded-[var(--radius-btn)] bg-accent px-4 py-2 font-display text-sm font-medium text-white transition hover:bg-accent/90">
                        Add item
                    </button>
                </div>
            @endif
        </div>

        {{-- Right: help --}}
        <div>
            <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-4 text-sm text-muted space-y-2">
                <p class="font-display text-xs font-semibold uppercase tracking-wide text-ink">How menus work</p>
                <p>Create a menu, assign it to a theme location, then add items. Items can point to a page, a post, or any custom URL.</p>
                <p>The theme declares locations (e.g. "primary", "footer") in <code>theme.json</code>. Use the <code>@{{menu('primary')}}</code> helper in your theme templates to render the menu.</p>
            </div>
        </div>
    </div>
</div>
