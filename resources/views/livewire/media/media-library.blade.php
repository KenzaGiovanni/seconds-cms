<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink">Media Library</h1>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-[var(--radius-btn)] border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Upload form --}}
    <div class="mb-8 rounded-[var(--radius-btn)] border border-line bg-bg p-6">
        <h2 class="mb-4 font-display text-sm font-semibold text-ink">Upload file</h2>
        <form wire:submit="store" class="space-y-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-muted" for="upload">File</label>
                <input
                    id="upload"
                    wire:model="upload"
                    type="file"
                    accept="image/*,application/pdf,video/mp4"
                    class="block w-full text-sm text-muted file:mr-4 file:rounded-[var(--radius-btn)] file:border-0 file:bg-accent file:px-3 file:py-1.5 file:font-display file:text-xs file:font-medium file:text-white hover:file:bg-accent/90"
                />
                @error('upload') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-muted" for="altText">Alt text</label>
                <input
                    id="altText"
                    wire:model="altText"
                    type="text"
                    placeholder="Describe the image for accessibility"
                    class="w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                />
            </div>
            <button type="submit"
                    class="rounded-[var(--radius-btn)] bg-accent px-4 py-2 font-display text-sm font-medium text-white transition hover:bg-accent/90">
                Upload
            </button>
        </form>
    </div>

    {{-- Grid --}}
    @if ($mediaItems->isEmpty())
        <div class="rounded-[var(--radius-btn)] border border-line bg-bg px-6 py-12 text-center text-sm text-muted">
            No files uploaded yet.
        </div>
    @else
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            @foreach ($mediaItems as $item)
                <div wire:key="{{ $item->id }}" class="group relative overflow-hidden rounded-[var(--radius-btn)] border border-line bg-bg">
                    <div class="aspect-square overflow-hidden bg-soft">
                        @if ($item->isImage())
                            <img src="{{ $item->url() }}" alt="{{ $item->alt }}" class="h-full w-full object-cover" />
                        @else
                            <div class="flex h-full items-center justify-center">
                                <span class="font-mono text-xs text-muted">{{ strtoupper(pathinfo($item->filename, PATHINFO_EXTENSION)) }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="p-2">
                        <p class="truncate text-xs text-ink" title="{{ $item->filename }}">{{ $item->filename }}</p>
                        <p class="text-xs text-muted">{{ number_format($item->size / 1024, 1) }} KB</p>
                    </div>
                    <div class="absolute inset-0 flex items-end justify-end bg-ink/0 p-2 opacity-0 transition group-hover:bg-ink/10 group-hover:opacity-100">
                        @if ($confirmingDelete === $item->id)
                            <div class="flex gap-1">
                                <button wire:click="delete({{ $item->id }})"
                                        class="rounded bg-red-600 px-2 py-1 text-xs font-medium text-white">
                                    Delete
                                </button>
                                <button wire:click="cancelDelete"
                                        class="rounded bg-bg px-2 py-1 text-xs font-medium text-ink">
                                    Cancel
                                </button>
                            </div>
                        @else
                            <button wire:click="confirmDelete({{ $item->id }})"
                                    class="rounded bg-red-600 px-2 py-1 text-xs font-medium text-white opacity-80 hover:opacity-100">
                                Delete
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
