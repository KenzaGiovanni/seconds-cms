<div>
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('admin.forms.index') }}" wire:navigate class="text-sm text-muted hover:text-ink">&larr; Forms</a>
        <h1 class="font-display text-2xl font-semibold tracking-tight text-ink">{{ $editing ? 'Edit Form' : 'New Form' }}</h1>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-[var(--radius-btn)] border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Fields --}}
        <div class="space-y-5 lg:col-span-2">
            <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-4 space-y-4">
                <h2 class="font-display text-sm font-semibold text-ink">Fields</h2>

                @forelse ($fields as $i => $field)
                    <div wire:key="field-{{ $i }}" class="rounded border border-line bg-soft p-3 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] uppercase tracking-wide text-muted">Field {{ $i + 1 }}</span>
                            <div class="flex gap-1">
                                <button type="button" wire:click="moveFieldUp({{ $i }})"
                                        class="rounded px-1.5 py-0.5 text-xs text-muted hover:text-ink">&uarr;</button>
                                <button type="button" wire:click="moveFieldDown({{ $i }})"
                                        class="rounded px-1.5 py-0.5 text-xs text-muted hover:text-ink">&darr;</button>
                                <button type="button" wire:click="removeField({{ $i }})"
                                        class="rounded px-1.5 py-0.5 text-xs text-red-500 hover:text-red-700">Remove</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-muted">Label</label>
                                <input wire:model="fields.{{ $i }}.label" type="text" placeholder="Your name"
                                       class="w-full rounded-[var(--radius-btn)] border border-line bg-bg px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent" />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-muted">Type</label>
                                <select wire:model.live="fields.{{ $i }}.type"
                                        class="w-full rounded-[var(--radius-btn)] border border-line bg-bg px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none">
                                    @foreach ($fieldTypes as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        @if (($field['type'] ?? '') === 'select')
                            <div>
                                <label class="mb-1 block text-xs font-medium text-muted">Options (comma-separated)</label>
                                <input wire:model="fields.{{ $i }}.options" type="text" placeholder="Sales, Support, Billing"
                                       class="w-full rounded-[var(--radius-btn)] border border-line bg-bg px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent" />
                            </div>
                        @endif

                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="checkbox" wire:model="fields.{{ $i }}.required"
                                   class="rounded border-line text-accent focus:ring-accent" />
                            <span class="text-xs text-muted">Required</span>
                        </label>
                    </div>
                @empty
                    <p class="text-xs text-muted">No fields yet. Add one below.</p>
                @endforelse

                <button type="button" wire:click="addField"
                        class="rounded-[var(--radius-btn)] border border-accent px-3 py-1.5 font-display text-xs font-medium text-accent transition hover:bg-accent/5">
                    + Add field
                </button>
            </div>
        </div>

        {{-- Settings --}}
        <div class="space-y-5">
            <div class="rounded-[var(--radius-btn)] border border-line bg-bg p-4 space-y-4">
                <h2 class="font-display text-sm font-semibold text-ink">Settings</h2>

                <div>
                    <label class="mb-1 block text-xs font-medium text-muted">Name</label>
                    <input wire:model.live="name" type="text" placeholder="Contact form"
                           class="w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent" />
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-muted">Slug <span class="font-normal">(for the shortcode)</span></label>
                    <input wire:model.live="slug" type="text" placeholder="contact"
                           class="w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3 py-2 font-mono text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent" />
                    @error('slug') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    @if ($slug)
                        <p class="mt-1 font-mono text-xs text-muted">&commat;form('{{ $slug }}')</p>
                    @endif
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-muted">Notify email <span class="font-normal">(optional)</span></label>
                    <input wire:model="recipientEmail" type="email" placeholder="you@example.com"
                           class="w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent" />
                    @error('recipientEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-muted">Success message</label>
                    <textarea wire:model="successMessage" rows="2"
                              class="w-full rounded-[var(--radius-btn)] border border-line bg-soft px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                    @error('successMessage') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit"
                        class="w-full rounded-[var(--radius-btn)] bg-accent py-2 font-display text-sm font-medium text-white transition hover:bg-accent/90">
                    {{ $editing ? 'Update form' : 'Save form' }}
                </button>
            </div>
        </div>
    </form>
</div>
