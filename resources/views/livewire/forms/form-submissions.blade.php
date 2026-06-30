<div>
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('admin.forms.index') }}" wire:navigate class="text-sm text-muted hover:text-ink">&larr; Forms</a>
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink">{{ $form->name }} &middot; Submissions</h1>
    </div>

    @if ($submissions->isEmpty())
        <div class="rounded-[var(--radius-btn)] border border-line bg-bg px-6 py-12 text-center text-sm text-muted">
            No submissions yet.
        </div>
    @else
        <div class="space-y-3">
            @foreach ($submissions as $submission)
                <div wire:key="sub-{{ $submission->id }}" class="rounded-[var(--radius-btn)] border border-line bg-bg p-4">
                    <div class="mb-2 text-xs text-muted">
                        {{ $submission->submitted_at?->format('d M Y, H:i') ?? $submission->created_at->format('d M Y, H:i') }}
                        @if ($submission->ip) &middot; {{ $submission->ip }} @endif
                    </div>
                    <dl class="grid grid-cols-1 gap-1 sm:grid-cols-2">
                        @foreach ($submission->data as $key => $value)
                            <div class="flex gap-2 text-sm">
                                <dt class="font-medium text-muted">{{ \Illuminate\Support\Str::headline($key) }}:</dt>
                                <dd class="text-ink">{{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endforeach
        </div>

        <div class="mt-4">{{ $submissions->links() }}</div>
    @endif
</div>
