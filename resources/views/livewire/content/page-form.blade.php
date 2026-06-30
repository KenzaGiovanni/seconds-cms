<div>
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('admin.pages.index') }}"
           wire:navigate
           class="text-sm text-muted transition hover:text-ink">&larr; Pages</a>
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink">
            {{ $editing ? 'Edit Page' : 'New Page' }}
        </h1>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-[var(--radius-btn)] border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Main content --}}
        <div class="space-y-5 lg:col-span-2">
            {{-- Title --}}
            <div>
                <label class="mb-1 block font-display text-sm font-medium text-ink" for="title">Title</label>
                <input
                    id="title"
                    wire:model.live="title"
                    type="text"
                    placeholder="Page title"
                    class="w-full rounded-[var(--radius-btn)] border border-line bg-bg px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                />
                @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Slug --}}
            <div>
                <label class="mb-1 block font-display text-sm font-medium text-ink" for="slug">
                    Slug
                    <span class="ml-1 font-normal text-muted">(URL path)</span>
                </label>
                <div class="flex items-center rounded-[var(--radius-btn)] border border-line bg-bg focus-within:border-accent focus-within:ring-1 focus-within:ring-accent">
                    <span class="border-r border-line px-3 py-2 text-sm text-muted">/</span>
                    <input
                        id="slug"
                        wire:model.live="slug"
                        type="text"
                        placeholder="page-slug"
                        class="flex-1 bg-transparent px-3 py-2 font-mono text-sm text-ink placeholder:text-muted focus:outline-none"
                    />
                </div>
                @error('slug') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Body --}}
            <div>
                <label class="mb-1 block font-display text-sm font-medium text-ink" for="body">Body</label>
                <textarea
                    id="body"
                    wire:model="body"
                    rows="12"
                    placeholder="Page content..."
                    class="w-full rounded-[var(--radius-btn)] border border-line bg-bg px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                ></textarea>
                @error('body') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Content blocks (schema-driven from the active theme) --}}
            @include('livewire.content.partials.block-editor')

            {{-- SEO --}}
            <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-4 space-y-4">
                <h2 class="font-display text-sm font-semibold text-ink">SEO</h2>
                <div>
                    <label class="mb-1 block text-xs font-medium text-muted" for="metaTitle">Meta title</label>
                    <input
                        id="metaTitle"
                        wire:model="metaTitle"
                        type="text"
                        placeholder="Defaults to page title"
                        class="w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                    />
                    @error('metaTitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-muted" for="metaDescription">Meta description</label>
                    <textarea
                        id="metaDescription"
                        wire:model="metaDescription"
                        rows="3"
                        placeholder="Short summary for search engines"
                        class="w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                    ></textarea>
                    @error('metaDescription') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">
            {{-- Featured image --}}
        <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-4 space-y-3">
            <h2 class="font-display text-sm font-semibold text-ink">Featured image</h2>
            @if ($featuredImageUrl)
                <img src="{{ $featuredImageUrl }}" alt="" class="w-full rounded object-cover" style="max-height:160px" />
                <button type="button" wire:click="removeFeaturedImage"
                        class="text-xs text-red-500 hover:text-red-700">Remove image</button>
            @else
                <p class="text-xs text-muted">No image selected.</p>
            @endif
            <livewire:media.media-picker />
        </div>

        {{-- Publish box --}}
            <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-4 space-y-4">
                <h2 class="font-display text-sm font-semibold text-ink">Publish</h2>

                <div>
                    <label class="mb-1 block text-xs font-medium text-muted" for="status">Status</label>
                    <select
                        id="status"
                        wire:model.live="status"
                        class="w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                    >
                        @foreach ($statuses as $s)
                            <option value="{{ $s->value }}">{{ $s->label() }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                @if (in_array($status, ['published', 'scheduled']))
                    <div>
                        <label class="mb-1 block text-xs font-medium text-muted" for="publishedAt">Publish date</label>
                        <input
                            id="publishedAt"
                            wire:model="publishedAt"
                            type="datetime-local"
                            class="w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                        />
                        @error('publishedAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <button
                    type="submit"
                    class="w-full rounded-[var(--radius-btn)] bg-accent py-2 font-display text-sm font-medium text-white transition hover:bg-accent/90">
                    {{ $editing ? 'Update page' : 'Save page' }}
                </button>
            </div>
        </div>
    </form>
</div>
