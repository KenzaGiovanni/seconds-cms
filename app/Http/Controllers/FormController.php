<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class FormController extends Controller
{
    /** Handle a public form submission: validate against the schema, capture it. */
    public function submit(Request $request, string $slug): RedirectResponse
    {
        $form = Form::findBySlug($slug);

        abort_unless($form, 404);

        // Honeypot: a filled hidden field means a bot. Pretend success, store nothing.
        if (filled($request->input('_hpot'))) {
            return back()->with('form_success', $slug);
        }

        $schemas = $form->fieldSchemas();
        $rules = [];
        $attributes = [];

        foreach ($schemas as $field) {
            $rule = [$field->required ? 'required' : 'nullable'];

            match ($field->type) {
                'email' => $rule[] = 'email',
                'number' => $rule[] = 'numeric',
                'toggle' => $rule[] = 'boolean',
                default => null,
            };

            $rules[$field->key] = $rule;
            $attributes[$field->key] = $field->label;
        }

        $validated = Validator::make($request->all(), $rules, [], $attributes)
            ->validateWithBag($slug);

        $keys = array_map(fn ($field) => $field->key, $schemas);

        FormSubmission::create([
            'form_id' => $form->id,
            'data' => Arr::only($validated, $keys),
            'ip' => $request->ip(),
            'submitted_at' => now(),
        ]);

        // Email notification is stubbed until mail is configured (recipient_email is stored).

        return back()->with('form_success', $slug);
    }
}
