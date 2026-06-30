{{--
    Renders one scalar block field as an input, bound to $path in $blocks.
    Params: $field (App\Support\FieldSchema), $path (string, e.g. "blocks.0.data.heading").
    Repeater fields are handled by the block editor, not here.
--}}
@php($inputClass = 'w-full rounded-[var(--radius-btn)] border border-line bg-bg px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent')

<div>
    @unless($field->type === 'toggle')
        <label class="mb-1 block text-xs font-medium text-muted">{{ $field->label }}</label>
    @endunless

    @if($field->type === 'textarea' || $field->type === 'richtext')
        <textarea wire:model="{{ $path }}" rows="3" class="{{ $inputClass }}"></textarea>

    @elseif($field->type === 'number')
        <input type="number" wire:model="{{ $path }}" class="{{ $inputClass }}" />

    @elseif($field->type === 'toggle')
        <label class="flex cursor-pointer items-center gap-2">
            <input type="checkbox" wire:model="{{ $path }}" class="rounded border-line text-accent focus:ring-accent" />
            <span class="text-sm text-muted">{{ $field->label }}</span>
        </label>

    @elseif($field->type === 'select')
        <select wire:model="{{ $path }}" class="{{ $inputClass }}">
            @foreach($field->options as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>

    @elseif($field->type === 'image')
        <div class="flex items-center gap-2">
            <input type="text" wire:model="{{ $path }}" placeholder="Image URL" class="{{ $inputClass }}" />
            <button type="button" wire:click="chooseBlockMedia('{{ $path }}')"
                    @class([
                        'shrink-0 rounded-[var(--radius-btn)] border px-3 py-2 text-xs font-medium transition',
                        'border-accent bg-accent/10 text-accent' => $mediaTargetPath === $path,
                        'border-line text-muted hover:text-ink' => $mediaTargetPath !== $path,
                    ])>
                {{ $mediaTargetPath === $path ? 'Pick below…' : 'Choose' }}
            </button>
        </div>

    @else
        <input type="text" wire:model="{{ $path }}" class="{{ $inputClass }}" />
    @endif

    @if($field->help)
        <p class="mt-1 text-xs text-muted">{{ $field->help }}</p>
    @endif
</div>
