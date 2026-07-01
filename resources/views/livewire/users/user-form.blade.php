<div>
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('admin.users.index') }}" wire:navigate class="text-sm text-muted transition hover:text-ink">&larr; Users</a>
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink">{{ $editing ? 'Edit User' : 'New User' }}</h1>
    </div>

    @php
        $input = 'w-full rounded-[var(--radius-btn)] border border-line bg-bg px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent';
        $label = 'mb-1.5 block font-display text-sm font-medium text-ink';
    @endphp

    <form wire:submit="save" class="max-w-xl space-y-5">
        <div>
            <label class="{{ $label }}">Name</label>
            <input wire:model="name" type="text" class="{{ $input }}">
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="{{ $label }}">Email</label>
            <input wire:model="email" type="email" class="{{ $input }}">
            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="{{ $label }}">Role</label>
            <select wire:model="role" class="{{ $input }}">
                @foreach ($roles as $r)
                    <option value="{{ $r->value }}">{{ $r->label() }}</option>
                @endforeach
            </select>
            @error('role') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-4 space-y-4">
            <h2 class="font-display text-sm font-semibold text-ink">
                {{ $editing ? 'Change password' : 'Password' }}
            </h2>
            @if ($editing)
                <p class="text-xs text-muted">Leave blank to keep the current password.</p>
            @endif
            <div>
                <label class="{{ $label }}">Password</label>
                <input wire:model="password" type="password" autocomplete="new-password" class="{{ $input }}">
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="{{ $label }}">Confirm password</label>
                <input wire:model="passwordConfirmation" type="password" autocomplete="new-password" class="{{ $input }}">
            </div>
        </div>

        <button type="submit"
                class="rounded-[var(--radius-btn)] bg-accent px-5 py-2 font-display text-sm font-medium text-white transition hover:bg-accent/90">
            {{ $editing ? 'Update user' : 'Create user' }}
        </button>
    </form>
</div>
