<div>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink">Website Settings</h1>
        <p class="mt-1 text-sm text-muted">Site identity, time, and what shows on your homepage.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-[var(--radius-btn)] border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @php
        $input = 'w-full rounded-[var(--radius-btn)] border border-line bg-bg px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent';
        $label = 'mb-1 block font-display text-sm font-medium text-ink';
    @endphp

    <form wire:submit="save" class="max-w-xl space-y-6">
        {{-- General --}}
        <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-5 space-y-4">
            <h2 class="font-display text-sm font-semibold text-ink">General</h2>

            <div>
                <label class="{{ $label }}" for="siteName">Site name</label>
                <input id="siteName" wire:model="siteName" type="text" class="{{ $input }}" />
                @error('siteName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="{{ $label }}" for="siteTagline">Tagline</label>
                <input id="siteTagline" wire:model="siteTagline" type="text" class="{{ $input }}" placeholder="A short description of your site" />
                @error('siteTagline') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="{{ $label }}" for="siteEmail">Admin email</label>
                <input id="siteEmail" wire:model="siteEmail" type="email" class="{{ $input }}" placeholder="you@example.com" />
                @error('siteEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Time --}}
        <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-5 space-y-4">
            <h2 class="font-display text-sm font-semibold text-ink">Time</h2>

            <div>
                <label class="{{ $label }}" for="timezone">Timezone</label>
                <select id="timezone" wire:model.live="timezone" class="{{ $input }}">
                    @foreach ($timezones as $tz)
                        <option value="{{ $tz }}">{{ $tz }}</option>
                    @endforeach
                </select>
                @error('timezone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="{{ $label }}" for="dateFormat">Date format</label>
                <select id="dateFormat" wire:model.live="dateFormat" class="{{ $input }}">
                    @foreach ($dateFormats as $fmt)
                        <option value="{{ $fmt }}">{{ now()->format($fmt) }} ({{ $fmt }})</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-muted">Preview: {{ now()->format($dateFormat ?: 'd M Y') }}</p>
                @error('dateFormat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Reading --}}
        <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-5 space-y-4">
            <h2 class="font-display text-sm font-semibold text-ink">Reading</h2>

            <div>
                <label class="{{ $label }}">Your homepage shows</label>
                <label class="flex cursor-pointer items-center gap-2 py-1">
                    <input type="radio" wire:model.live="showOnFront" value="posts" class="text-accent focus:ring-accent" />
                    <span class="text-sm text-ink">Your latest posts</span>
                </label>
                <label class="flex cursor-pointer items-center gap-2 py-1">
                    <input type="radio" wire:model.live="showOnFront" value="page" class="text-accent focus:ring-accent" />
                    <span class="text-sm text-ink">A static page</span>
                </label>
            </div>

            @if ($showOnFront === 'page')
                <div>
                    <label class="{{ $label }}" for="frontPageId">Homepage</label>
                    <select id="frontPageId" wire:model="frontPageId" class="{{ $input }}">
                        <option value="">- Select a page -</option>
                        @foreach ($pages as $page)
                            <option value="{{ $page->id }}">{{ $page->title }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-muted">Only published pages render at the root; drafts fall back to the posts feed.</p>
                    @error('frontPageId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif

            <div>
                <label class="{{ $label }}" for="postsPerPage">Posts per page</label>
                <input id="postsPerPage" wire:model="postsPerPage" type="number" min="1" max="100" class="{{ $input }}" />
                @error('postsPerPage') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <button type="submit"
                class="rounded-[var(--radius-btn)] bg-accent px-4 py-2 font-display text-sm font-medium text-white transition hover:bg-accent/90">
            Save settings
        </button>
    </form>
</div>
