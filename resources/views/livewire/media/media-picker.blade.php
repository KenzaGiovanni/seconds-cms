<div>
    <button type="button"
            wire:click="$set('open', true)"
            class="rounded-[var(--radius-btn)] border border-line px-3 py-1.5 font-display text-xs font-medium text-muted transition hover:border-accent hover:text-accent">
        Choose from library
    </button>

    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4" wire:click.self="$set('open', false)">
            <div class="relative flex max-h-[80vh] w-full max-w-3xl flex-col overflow-hidden rounded-[var(--radius-btn)] bg-bg shadow-xl">
                <div class="flex items-center justify-between border-b border-line px-6 py-4">
                    <h2 class="font-display text-base font-semibold text-ink">Media Library</h2>
                    <button type="button" wire:click="$set('open', false)" class="text-muted hover:text-ink">&times;</button>
                </div>
                <div class="flex-1 overflow-y-auto p-6">
                    @if ($mediaItems->isEmpty())
                        <p class="text-sm text-muted">No files uploaded yet. Upload some in the Media Library.</p>
                    @else
                        <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-5">
                            @foreach ($mediaItems as $item)
                                <button type="button"
                                        wire:click="pick({{ $item->id }})"
                                        @class([
                                            'overflow-hidden rounded-[var(--radius-btn)] border transition hover:border-accent',
                                            'border-accent ring-2 ring-accent' => $selected === $item->id,
                                            'border-line' => $selected !== $item->id,
                                        ])>
                                    <div class="aspect-square overflow-hidden bg-soft">
                                        @if ($item->isImage())
                                            <img src="{{ $item->url() }}" alt="{{ $item->alt }}" class="h-full w-full object-cover" />
                                        @else
                                            <div class="flex h-full items-center justify-center">
                                                <span class="font-mono text-xs text-muted">{{ strtoupper(pathinfo($item->filename, PATHINFO_EXTENSION)) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <p class="truncate px-1 py-1 text-xs text-muted">{{ $item->filename }}</p>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
