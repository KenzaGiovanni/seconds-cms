<div>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink">Theme Code</h1>
        <p class="mt-1 text-sm text-muted">Edit theme template files directly. Changes take effect immediately - each save is backed up.</p>
    </div>

    <div class="mb-4 rounded-[var(--radius-btn)] border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-900">
        <strong>Not recommended unless you know what you are doing.</strong>
        This editor is for coders only. Theme files run on the live server, so a single broken template
        can take your whole site down. Changes apply immediately. Every save is backed up, but edit with care -
        or turn this off under Themes.
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-[var(--radius-btn)] border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-[var(--radius-btn)] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
        {{-- File tree --}}
        <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-2 lg:col-span-1">
            <p class="px-2 py-1.5 text-[11px] font-semibold uppercase tracking-wider text-muted/70">Files</p>
            <ul class="max-h-[32rem] space-y-0.5 overflow-auto">
                @foreach ($files as $file)
                    <li>
                        <button type="button" wire:click="selectFile('{{ $file }}')"
                                @class([
                                    'w-full truncate rounded px-2 py-1 text-left font-mono text-xs transition',
                                    'bg-accent/10 text-accent' => $currentFile === $file,
                                    'text-muted hover:bg-soft hover:text-ink' => $currentFile !== $file,
                                ])
                                title="{{ $file }}">
                            {{ $file }}
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Editor --}}
        <div class="lg:col-span-3">
            @if ($currentFile)
                <form wire:submit="save" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-xs text-muted">themes/{{ $currentFile }}</span>
                        <button type="submit"
                                class="rounded-[var(--radius-btn)] bg-accent px-4 py-1.5 font-display text-sm font-medium text-white transition hover:bg-accent/90">
                            Save
                        </button>
                    </div>
                    <textarea wire:model="content" spellcheck="false"
                              class="h-[32rem] w-full rounded-[var(--radius-btn)] border border-line bg-[#101413] p-4 font-mono text-xs leading-relaxed text-[#e6e8e5] focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                </form>
            @else
                <div class="flex h-[32rem] items-center justify-center rounded-[var(--radius-btn)] border border-dashed border-line bg-bg text-sm text-muted">
                    Select a file to edit.
                </div>
            @endif
        </div>
    </div>
</div>
