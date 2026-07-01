<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink">Themes</h1>
        <a href="{{ route('admin.themes.settings') }}" wire:navigate
           class="text-sm text-muted hover:text-ink">Theme settings &rarr;</a>
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

    {{-- Upload ZIP --}}
    <div class="mb-8 rounded-[var(--radius-btn)] border border-line bg-bg p-5 space-y-4">
        <h2 class="font-display text-sm font-semibold text-ink">Install a theme</h2>
        <form wire:submit="install" class="flex items-end gap-3">
            <div class="flex-1">
                <label class="mb-1 block text-xs font-medium text-muted">Theme ZIP</label>
                <input wire:model="zipFile" type="file" accept=".zip"
                       class="block w-full text-sm text-muted file:mr-4 file:rounded-[var(--radius-btn)] file:border-0 file:bg-accent file:px-3 file:py-1.5 file:font-display file:text-xs file:font-medium file:text-white hover:file:bg-accent/90" />
                @error('zipFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit"
                    class="rounded-[var(--radius-btn)] bg-accent px-4 py-2 font-display text-sm font-medium text-white transition hover:bg-accent/90">
                Install
            </button>
        </form>
    </div>

    {{-- Installed themes --}}
    <div class="overflow-hidden rounded-[var(--radius-btn)] border border-line bg-bg">
        <table class="w-full text-sm">
            <thead class="border-b border-line bg-soft">
                <tr>
                    <th class="px-4 py-3 text-left font-display font-medium text-ink">Theme</th>
                    <th class="px-4 py-3 text-left font-display font-medium text-ink">Version</th>
                    <th class="px-4 py-3 text-left font-display font-medium text-ink">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($themes as $theme)
                    <tr wire:key="{{ $theme->id }}">
                        <td class="px-4 py-3">
                            <div class="font-medium text-ink">{{ $theme->name }}</div>
                            @if ($theme->author)
                                <div class="text-xs text-muted">by {{ $theme->author }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $theme->version }}</td>
                        <td class="px-4 py-3">
                            @if ($theme->isActive())
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">Active</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">Installed</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @unless ($theme->isActive())
                                    <button wire:click="activate({{ $theme->id }})"
                                            class="rounded px-2 py-1 text-xs font-medium text-accent transition hover:text-accent/80">
                                        Activate
                                    </button>
                                @endunless

                                @if ($confirmingUninstall === $theme->id)
                                    <span class="text-xs text-muted">Sure?</span>
                                    <button wire:click="uninstall({{ $theme->id }})"
                                            class="rounded px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800">
                                        Yes, uninstall
                                    </button>
                                    <button wire:click="cancelUninstall"
                                            class="rounded px-2 py-1 text-xs font-medium text-muted hover:text-ink">
                                        Cancel
                                    </button>
                                @else
                                    <button wire:click="confirmUninstall({{ $theme->id }})"
                                            class="rounded px-2 py-1 text-xs font-medium text-muted transition hover:text-red-600">
                                        Uninstall
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-muted">No themes installed.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Theme code editor (developer / super-admin only) --}}
    @can(\App\Enums\Permission::ThemesEditCode->value)
        <div class="mt-8 rounded-[var(--radius-btn)] border border-line bg-bg p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-display text-sm font-semibold text-ink">Theme code editor</h2>
                    <p class="mt-1 max-w-lg text-xs text-muted">
                        Edit theme template files directly from the admin. This is advanced - a broken
                        template can take the site down, and editing code runs on the live server.
                        For developers only.
                    </p>
                    <p class="mt-2 text-xs">
                        Status:
                        @if ($editorEnabled)
                            <span class="font-medium text-accent">Enabled</span>
                            &middot; <a href="{{ route('admin.themes.code') }}" wire:navigate class="text-accent hover:underline">Open editor</a>
                        @else
                            <span class="font-medium text-muted">Disabled</span>
                        @endif
                    </p>
                </div>
                <button type="button" wire:click="promptEditorToggle"
                        @class([
                            'shrink-0 rounded-[var(--radius-btn)] px-4 py-2 font-display text-sm font-medium transition',
                            'border border-line text-ink hover:bg-soft' => $editorEnabled,
                            'bg-accent text-white hover:bg-accent/90' => ! $editorEnabled,
                        ])>
                    {{ $editorEnabled ? 'Disable' : 'Enable' }}
                </button>
            </div>
        </div>

        {{-- Confirm modal --}}
        @if ($confirmingEditorToggle)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4" wire:click.self="cancelEditorToggle">
                <div class="w-full max-w-md rounded-[var(--radius-card)] border border-line bg-bg p-6 shadow-xl">
                    <h3 class="font-display text-lg font-semibold tracking-tight text-ink">
                        {{ $editorEnabled ? 'Disable the theme code editor?' : 'Enable the theme code editor?' }}
                    </h3>
                    @if ($editorEnabled)
                        <p class="mt-2 text-sm text-muted">
                            The Theme Code screen will be hidden and locked. You can turn it back on here at any time.
                        </p>
                    @else
                        <p class="mt-2 text-sm text-muted">
                            This lets developer / super-admin users edit theme code from the browser. Because
                            templates run on the live server, a mistake can break your site. Only enable this
                            if you know what you are doing.
                        </p>
                    @endif
                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" wire:click="cancelEditorToggle"
                                class="rounded-[var(--radius-btn)] border border-line px-4 py-2 font-display text-sm font-medium text-ink transition hover:bg-soft">
                            Cancel
                        </button>
                        <button type="button" wire:click="toggleThemeEditor"
                                @class([
                                    'rounded-[var(--radius-btn)] px-4 py-2 font-display text-sm font-medium text-white transition',
                                    'bg-red-600 hover:bg-red-700' => $editorEnabled,
                                    'bg-accent hover:bg-accent/90' => ! $editorEnabled,
                                ])>
                            {{ $editorEnabled ? 'Yes, disable' : 'Yes, enable' }}
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endcan
</div>
