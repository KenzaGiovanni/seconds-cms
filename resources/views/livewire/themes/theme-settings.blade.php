<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold text-ink">Theme Settings</h1>
            @if ($activeTheme)
                <p class="mt-1 text-sm text-muted">Active theme: <span class="font-medium text-ink">{{ $activeTheme->name }}</span></p>
            @endif
        </div>
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

    @if (! $activeTheme || empty($schema))
        <div class="rounded-[var(--radius-btn)] border border-line bg-bg px-6 py-12 text-center text-sm text-muted">
            @if (! $activeTheme)
                No active theme. Activate a theme to edit its settings.
            @else
                This theme has no configurable settings.
            @endif
        </div>
    @else
        <form wire:submit="save" class="max-w-xl space-y-5">
            @foreach ($schema as $key => $default)
                <div>
                    <label class="mb-1 block font-display text-sm font-medium text-ink" for="setting-{{ $key }}">
                        {{ ucwords(str_replace(['_', '-'], ' ', $key)) }}
                    </label>
                    @if(is_bool($default))
                        <label class="flex cursor-pointer items-center gap-2">
                            <input
                                id="setting-{{ $key }}"
                                wire:model="settings.{{ $key }}"
                                type="checkbox"
                                class="rounded border-line text-accent focus:ring-accent"
                            />
                            <span class="text-sm text-muted">Enable</span>
                        </label>
                    @elseif(is_string($default) && preg_match('/^#[0-9a-fA-F]{3,6}$/', $default))
                        <input
                            id="setting-{{ $key }}"
                            wire:model="settings.{{ $key }}"
                            type="color"
                            class="h-10 w-24 cursor-pointer rounded-[var(--radius-btn)] border border-line bg-bg p-1"
                        />
                        <span class="ml-2 text-xs text-muted font-mono">{{ $settings[$key] ?? $default }}</span>
                    @else
                        <input
                            id="setting-{{ $key }}"
                            wire:model="settings.{{ $key }}"
                            type="text"
                            class="w-full rounded-[var(--radius-btn)] border border-line bg-bg px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                        />
                    @endif
                </div>
            @endforeach

            <button type="submit"
                    class="rounded-[var(--radius-btn)] bg-accent px-4 py-2 font-display text-sm font-medium text-white transition hover:bg-accent/90">
                Save settings
            </button>
        </form>
    @endif
</div>
