<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="font-display text-2xl font-bold text-ink">Posts</h1>
        <a href="{{ route('admin.posts.create') }}"
           wire:navigate
           class="rounded-[var(--radius-btn)] bg-accent px-4 py-2 font-display text-sm font-medium text-white transition hover:bg-accent/90">
            New post
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-[var(--radius-btn)] border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($posts->isEmpty())
        <div class="rounded-[var(--radius-btn)] border border-line bg-bg px-6 py-12 text-center text-sm text-muted">
            No posts yet. <a href="{{ route('admin.posts.create') }}" wire:navigate class="text-accent underline">Write your first post.</a>
        </div>
    @else
        <div class="overflow-hidden rounded-[var(--radius-btn)] border border-line bg-bg">
            <table class="w-full text-sm">
                <thead class="border-b border-line bg-soft">
                    <tr>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Title</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Slug</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Categories</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Status</th>
                        <th class="px-4 py-3 text-left font-display font-medium text-ink">Published</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($posts as $post)
                        <tr wire:key="{{ $post->id }}">
                            <td class="px-4 py-3 font-medium text-ink">
                                {{ $post->title }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-muted">
                                /blog/{{ $post->slug }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($post->categories as $cat)
                                        <span class="inline-flex items-center rounded-full bg-soft px-2 py-0.5 text-xs text-muted">{{ $cat->name }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-green-100 text-green-800' => $post->status->value === 'published',
                                    'bg-yellow-100 text-yellow-800' => $post->status->value === 'scheduled',
                                    'bg-gray-100 text-gray-600' => $post->status->value === 'draft',
                                ])>
                                    {{ $post->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted">
                                {{ $post->published_at?->format('d M Y, H:i') ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.posts.edit', $post->id) }}"
                                       wire:navigate
                                       class="rounded px-2 py-1 text-xs font-medium text-muted transition hover:text-ink">
                                        Edit
                                    </a>

                                    @if ($confirmingDelete === $post->id)
                                        <span class="text-xs text-muted">Sure?</span>
                                        <button wire:click="delete({{ $post->id }})"
                                                class="rounded px-2 py-1 text-xs font-medium text-red-600 transition hover:text-red-800">
                                            Yes, delete
                                        </button>
                                        <button wire:click="cancelDelete"
                                                class="rounded px-2 py-1 text-xs font-medium text-muted transition hover:text-ink">
                                            Cancel
                                        </button>
                                    @else
                                        <button wire:click="confirmDelete({{ $post->id }})"
                                                class="rounded px-2 py-1 text-xs font-medium text-muted transition hover:text-red-600">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
