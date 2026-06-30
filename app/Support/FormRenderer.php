<?php

namespace App\Support;

use App\Models\Form;

/**
 * Renders a stored {@see Form} into HTML via the active theme's
 * `theme::forms.form` partial. Backs the `@form('slug')` Blade directive and
 * the `form` block. Returns an empty string for an unknown slug so a missing
 * form never fatals a page.
 */
class FormRenderer
{
    public static function render(?string $slug): string
    {
        if (! $slug) {
            return '';
        }

        $form = Form::findBySlug($slug);

        if (! $form) {
            return '';
        }

        return view('theme::forms.form', ['form' => $form])->render();
    }
}
