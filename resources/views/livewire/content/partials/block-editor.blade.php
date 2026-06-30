{{--
    Schema-driven block editor. Reads the active theme's block definitions and
    renders an input form per block from its field schema. Used by page + post
    forms via the WithBlockEditor trait. Requires $blocks + $mediaTargetPath.
--}}
@php($definitions = $this->blockDefinitions())

<div class="rounded-[var(--radius-btn)] border border-line bg-bg p-4 space-y-4">
    <h2 class="font-display text-sm font-semibold text-ink">Content blocks</h2>

    @forelse($blocks as $i => $block)
        @php($def = $definitions[$block['type']] ?? null)
        <div wire:key="block-{{ $i }}" class="rounded border border-line bg-soft p-3 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-muted">
                    {{ $def?->label ?? $block['type'] }}
                </span>
                <div class="flex gap-1">
                    <button type="button" wire:click="moveBlockUp({{ $i }})"
                            class="rounded px-1.5 py-0.5 text-xs text-muted hover:text-ink">&uarr;</button>
                    <button type="button" wire:click="moveBlockDown({{ $i }})"
                            class="rounded px-1.5 py-0.5 text-xs text-muted hover:text-ink">&darr;</button>
                    <button type="button" wire:click="removeBlock({{ $i }})"
                            class="rounded px-1.5 py-0.5 text-xs text-red-500 hover:text-red-700">Remove</button>
                </div>
            </div>

            @if($def)
                @foreach($def->fields as $field)
                    @if($field->type === 'repeater')
                        <div class="rounded border border-line bg-bg p-3 space-y-3">
                            <p class="text-xs font-medium text-muted">{{ $field->label }}</p>

                            @foreach($block['data'][$field->key] ?? [] as $j => $item)
                                <div wire:key="rep-{{ $i }}-{{ $field->key }}-{{ $j }}"
                                     class="rounded border border-line bg-soft p-3 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[11px] uppercase tracking-wide text-muted">Item {{ $j + 1 }}</span>
                                        <button type="button"
                                                wire:click="removeRepeaterItem({{ $i }}, '{{ $field->key }}', {{ $j }})"
                                                class="rounded px-1.5 py-0.5 text-xs text-red-500 hover:text-red-700">Remove</button>
                                    </div>
                                    @foreach($field->fields as $sub)
                                        @include('livewire.content.partials.block-field', [
                                            'field' => $sub,
                                            'path' => "blocks.{$i}.data.{$field->key}.{$j}.{$sub->key}",
                                        ])
                                    @endforeach
                                </div>
                            @endforeach

                            <button type="button" wire:click="addRepeaterItem({{ $i }}, '{{ $field->key }}')"
                                    class="rounded-[var(--radius-btn)] border border-accent px-3 py-1.5 font-display text-xs font-medium text-accent transition hover:bg-accent/5">
                                + Add {{ \Illuminate\Support\Str::singular($field->label) }}
                            </button>
                        </div>
                    @else
                        @include('livewire.content.partials.block-field', [
                            'field' => $field,
                            'path' => "blocks.{$i}.data.{$field->key}",
                        ])
                    @endif
                @endforeach
            @else
                <p class="text-xs text-red-600">Unknown block type "{{ $block['type'] }}" - not provided by the active theme.</p>
            @endif
        </div>
    @empty
        <p class="text-xs text-muted">No blocks yet. Add one below.</p>
    @endforelse

    <div class="flex items-center gap-2">
        <select wire:model="newBlockType"
                class="rounded-[var(--radius-btn)] border border-line bg-soft px-3 py-1.5 text-sm text-ink focus:border-accent focus:outline-none">
            @foreach($this->blockOptions() as $type => $label)
                <option value="{{ $type }}">{{ $label }}</option>
            @endforeach
        </select>
        <button type="button" wire:click="addBlock"
                class="rounded-[var(--radius-btn)] border border-accent px-3 py-1.5 font-display text-xs font-medium text-accent transition hover:bg-accent/5">
            + Add block
        </button>
    </div>
</div>
