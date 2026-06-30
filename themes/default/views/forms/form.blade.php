@php($slug = $form->slug)
@php($bag = $errors->getBag($slug))

<form method="POST" action="{{ route('forms.submit', $slug) }}" class="seconds-form">
    @csrf

    @if(session('form_success') === $slug)
        <p class="seconds-form-success">{{ $form->success_message }}</p>
    @endif

    {{-- Honeypot: hidden from humans; bots that fill it are dropped. --}}
    <div class="seconds-form-hp" aria-hidden="true">
        <label>Leave this field empty
            <input type="text" name="_hpot" tabindex="-1" autocomplete="off">
        </label>
    </div>

    @foreach($form->fieldSchemas() as $field)
        @php($id = "f-{$slug}-{$field->key}")
        <div class="seconds-form-field">
            @if($field->type !== 'toggle')
                <label for="{{ $id }}">{{ $field->label }}@if($field->required) <span class="req">*</span>@endif</label>
            @endif

            @switch($field->type)
                @case('textarea')
                    <textarea id="{{ $id }}" name="{{ $field->key }}" rows="4">{{ old($field->key) }}</textarea>
                    @break

                @case('select')
                    <select id="{{ $id }}" name="{{ $field->key }}">
                        <option value="">-- Select --</option>
                        @foreach($field->options as $value => $label)
                            <option value="{{ $value }}" @selected(old($field->key) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @break

                @case('toggle')
                    <label class="seconds-form-check">
                        <input type="checkbox" id="{{ $id }}" name="{{ $field->key }}" value="1" @checked(old($field->key))>
                        {{ $field->label }}
                    </label>
                    @break

                @case('email')
                    <input type="email" id="{{ $id }}" name="{{ $field->key }}" value="{{ old($field->key) }}">
                    @break

                @case('number')
                    <input type="number" id="{{ $id }}" name="{{ $field->key }}" value="{{ old($field->key) }}">
                    @break

                @default
                    <input type="text" id="{{ $id }}" name="{{ $field->key }}" value="{{ old($field->key) }}">
            @endswitch

            @if($bag->has($field->key))
                <p class="seconds-form-error">{{ $bag->first($field->key) }}</p>
            @endif
        </div>
    @endforeach

    <button type="submit" class="seconds-form-submit">Send</button>
</form>
