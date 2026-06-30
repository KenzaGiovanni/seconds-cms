<?php

namespace App\Livewire\Forms;

use App\Enums\Permission;
use App\Models\Form;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class FormBuilder extends Component
{
    public ?int $formId = null;

    public string $name = '';

    public string $slug = '';

    public string $recipientEmail = '';

    public string $successMessage = 'Thanks - your message has been sent.';

    public bool $slugManuallyEdited = false;

    /** @var list<array{label: string, key: string, type: string, required: bool, options: string}> */
    public array $fields = [];

    /** Field types offered in the form builder (a safe subset of the engine). */
    public array $fieldTypes = [
        'text' => 'Text',
        'textarea' => 'Text area',
        'email' => 'Email',
        'number' => 'Number',
        'select' => 'Dropdown',
        'toggle' => 'Checkbox',
    ];

    public function mount(?int $id = null): void
    {
        abort_unless(auth()->user()->can(Permission::ContentManage->value), 403);

        if ($id) {
            $form = Form::findOrFail($id);
            $this->formId = $form->id;
            $this->name = $form->name;
            $this->slug = $form->slug;
            $this->recipientEmail = $form->recipient_email ?? '';
            $this->successMessage = $form->success_message ?? '';
            $this->slugManuallyEdited = true;

            $this->fields = array_map(function (array $f) {
                return [
                    'label' => $f['label'] ?? '',
                    'key' => $f['key'] ?? '',
                    'type' => $f['type'] ?? 'text',
                    'required' => (bool) ($f['required'] ?? false),
                    'options' => isset($f['options']) ? implode(', ', $f['options']) : '',
                ];
            }, $form->fields ?? []);
        }
    }

    public function updatedName(string $value): void
    {
        if (! $this->slugManuallyEdited) {
            $this->slug = Str::slug($value);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugManuallyEdited = true;
    }

    public function addField(): void
    {
        $this->fields[] = ['label' => '', 'key' => '', 'type' => 'text', 'required' => false, 'options' => ''];
    }

    public function removeField(int $index): void
    {
        array_splice($this->fields, $index, 1);
        $this->fields = array_values($this->fields);
    }

    public function moveFieldUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->fields[$index])) {
            return;
        }

        [$this->fields[$index - 1], $this->fields[$index]] = [$this->fields[$index], $this->fields[$index - 1]];
    }

    public function moveFieldDown(int $index): void
    {
        if ($index >= count($this->fields) - 1) {
            return;
        }

        [$this->fields[$index], $this->fields[$index + 1]] = [$this->fields[$index + 1], $this->fields[$index]];
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can(Permission::ContentManage->value), 403);

        $this->slug = Str::slug($this->slug) ?: Str::slug($this->name);

        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'required', 'string', 'max:255', 'regex:/^[a-z0-9][a-z0-9\-]*$/',
                function ($attribute, $value, $fail) {
                    $exists = Form::where('slug', $value)
                        ->when($this->formId, fn ($q) => $q->where('id', '!=', $this->formId))
                        ->exists();
                    if ($exists) {
                        $fail('This slug is already taken.');
                    }
                },
            ],
            'recipientEmail' => 'nullable|email',
            'successMessage' => 'nullable|string|max:500',
        ]);

        $payload = [
            'name' => $this->name,
            'slug' => $this->slug,
            'fields' => $this->buildSchema(),
            'recipient_email' => $this->recipientEmail ?: null,
            'success_message' => $this->successMessage ?: 'Thanks - your message has been sent.',
        ];

        if ($this->formId) {
            Form::findOrFail($this->formId)->update($payload);
            session()->flash('success', 'Form updated.');
        } else {
            Form::create($payload);
            session()->flash('success', 'Form created.');
        }

        $this->redirect(route('admin.forms.index'), navigate: true);
    }

    /** Turn the builder rows into a stored field-schema array. */
    private function buildSchema(): array
    {
        $schema = [];

        foreach ($this->fields as $field) {
            $label = trim($field['label'] ?? '');

            if ($label === '') {
                continue;
            }

            $key = trim($field['key'] ?? '') !== ''
                ? Str::slug($field['key'], '_')
                : Str::slug($label, '_');

            $entry = [
                'key' => $key,
                'type' => array_key_exists($field['type'] ?? '', $this->fieldTypes) ? $field['type'] : 'text',
                'label' => $label,
                'required' => (bool) ($field['required'] ?? false),
            ];

            if ($entry['type'] === 'select') {
                $entry['options'] = collect(explode(',', $field['options'] ?? ''))
                    ->map(fn ($o) => trim($o))
                    ->filter()
                    ->mapWithKeys(fn ($o) => [Str::slug($o, '_') => $o])
                    ->all();
            }

            $schema[] = $entry;
        }

        return $schema;
    }

    public function render()
    {
        return view('livewire.forms.form-builder', [
            'editing' => $this->formId !== null,
        ]);
    }

    public function title(): string
    {
        return $this->formId ? 'Edit Form' : 'New Form';
    }
}
