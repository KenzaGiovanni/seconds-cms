<div>
    <h1 class="font-display text-xl font-semibold text-ink">Sign in</h1>
    <p class="mt-1 text-sm text-muted">Welcome back. Enter your details to continue.</p>

    <form wire:submit="authenticate" class="mt-6 space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium text-ink">Email</label>
            <input
                wire:model="email"
                id="email"
                type="email"
                autocomplete="email"
                autofocus
                class="mt-1.5 block w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3.5 py-2.5 text-sm text-ink placeholder-muted focus:border-accent focus:bg-bg focus:outline-none focus:ring-2 focus:ring-accent/20"
                placeholder="you@studio.com"
            >
            @error('email') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-ink">Password</label>
            <input
                wire:model="password"
                id="password"
                type="password"
                autocomplete="current-password"
                class="mt-1.5 block w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3.5 py-2.5 text-sm text-ink placeholder-muted focus:border-accent focus:bg-bg focus:outline-none focus:ring-2 focus:ring-accent/20"
                placeholder="••••••••"
            >
            @error('password') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-muted">
            <input wire:model="remember" type="checkbox" class="rounded border-line text-accent focus:ring-accent/30">
            Remember me
        </label>

        <button
            type="submit"
            class="w-full rounded-[var(--radius-btn)] bg-accent px-4 py-2.5 font-display text-sm font-medium text-white transition hover:bg-accent-2 focus:outline-none focus:ring-2 focus:ring-accent/30 disabled:opacity-60"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove wire:target="authenticate">Sign in</span>
            <span wire:loading wire:target="authenticate">Signing in…</span>
        </button>
    </form>
</div>
