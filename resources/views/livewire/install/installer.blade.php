<div class="w-full">
    <div class="mb-8 text-center">
        <h1 class="font-display text-2xl font-bold text-ink">Set up Seconds</h1>
        <p class="mt-1 text-sm text-muted">You only need to do this once.</p>
    </div>

    <form wire:submit="install" class="space-y-5">
        <div>
            <label for="siteName" class="block text-sm font-medium text-ink">Site name</label>
            <input id="siteName" type="text" wire:model="siteName" autocomplete="off"
                   class="mt-1 w-full rounded-[var(--radius-input)] border border-line bg-bg px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none" />
            @error('siteName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-ink">Admin email</label>
            <input id="email" type="email" wire:model="email" autocomplete="email"
                   class="mt-1 w-full rounded-[var(--radius-input)] border border-line bg-bg px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none" />
            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-ink">Password</label>
            <input id="password" type="password" wire:model="password" autocomplete="new-password"
                   class="mt-1 w-full rounded-[var(--radius-input)] border border-line bg-bg px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none" />
            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="passwordConfirmation" class="block text-sm font-medium text-ink">Confirm password</label>
            <input id="passwordConfirmation" type="password" wire:model="passwordConfirmation" autocomplete="new-password"
                   class="mt-1 w-full rounded-[var(--radius-input)] border border-line bg-bg px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none" />
        </div>

        <button type="submit" wire:loading.attr="disabled"
                class="w-full rounded-[var(--radius-btn)] bg-accent px-4 py-2.5 font-display text-sm font-semibold text-white transition hover:bg-accent/90 disabled:opacity-50">
            <span wire:loading.remove>Install Seconds</span>
            <span wire:loading>Installing...</span>
        </button>
    </form>
</div>
