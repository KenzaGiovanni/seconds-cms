<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink">Users</h1>
        <a href="{{ route('admin.users.create') }}"
           wire:navigate
           class="rounded-[var(--radius-btn)] bg-accent px-4 py-2 font-display text-sm font-medium text-white transition hover:bg-accent/90">
            New user
        </a>
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

    <div class="overflow-hidden rounded-[var(--radius-btn)] border border-line bg-bg">
        <table class="w-full text-sm">
            <thead class="border-b border-line bg-soft">
                <tr>
                    <th class="px-4 py-3 text-left font-display font-medium text-ink">Name</th>
                    <th class="px-4 py-3 text-left font-display font-medium text-ink">Email</th>
                    <th class="px-4 py-3 text-left font-display font-medium text-ink">Role</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($users as $user)
                    <tr wire:key="{{ $user->id }}">
                        <td class="px-4 py-3 font-medium text-ink">
                            {{ $user->name }}
                            @if ($user->id === auth()->id())
                                <span class="ml-1 text-xs text-muted">(you)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            @forelse ($user->roles as $role)
                                <span class="inline-flex items-center rounded-full bg-accent/10 px-2 py-0.5 text-xs font-medium text-accent">
                                    {{ \App\Enums\Role::from($role->name)->label() }}
                                </span>
                            @empty
                                <span class="text-xs text-muted">No role</span>
                            @endforelse
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.users.edit', $user->id) }}"
                                   wire:navigate
                                   class="rounded px-2 py-1 text-xs font-medium text-muted transition hover:text-ink">
                                    Edit
                                </a>

                                @if ($user->id !== auth()->id())
                                    @if ($confirmingDelete === $user->id)
                                        <span class="text-xs text-muted">Sure?</span>
                                        <button wire:click="delete({{ $user->id }})"
                                                class="rounded px-2 py-1 text-xs font-medium text-red-600 transition hover:text-red-800">
                                            Yes, delete
                                        </button>
                                        <button wire:click="cancelDelete"
                                                class="rounded px-2 py-1 text-xs font-medium text-muted transition hover:text-ink">
                                            Cancel
                                        </button>
                                    @else
                                        <button wire:click="confirmDelete({{ $user->id }})"
                                                class="rounded px-2 py-1 text-xs font-medium text-muted transition hover:text-red-600">
                                            Delete
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
