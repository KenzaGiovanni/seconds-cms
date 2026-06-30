<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\Form;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Loads a sample page + contact form that exercise the default theme's block
 * library end to end. Run on demand (not part of DatabaseSeeder so it never
 * touches a real install or the test suite):
 *
 *   php artisan db:seed --class=DemoContentSeeder
 *
 * Idempotent - safe to re-run. Visit /sample to see the result.
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $form = Form::firstOrCreate(
            ['slug' => 'contact'],
            [
                'name' => 'Contact',
                'fields' => [
                    ['key' => 'name', 'type' => 'text', 'label' => 'Your name', 'required' => true],
                    ['key' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true],
                    ['key' => 'topic', 'type' => 'select', 'label' => 'Topic',
                        'options' => ['general' => 'General', 'project' => 'New project', 'support' => 'Support']],
                    ['key' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => true],
                ],
                'success_message' => 'Thanks - we will be in touch shortly.',
            ],
        );

        Page::firstOrCreate(
            ['slug' => 'sample'],
            [
                'title' => 'Sample Page',
                'status' => ContentStatus::Published,
                'published_at' => now(),
                'blocks' => [
                    ['type' => 'hero', 'data' => [
                        'heading' => 'Build it once. Update it in seconds.',
                        'subheading' => 'A clean, modern site backed by a CMS that stays out of your way.',
                        'cta_label' => 'Get in touch',
                        'cta_url' => '#contact',
                        'image' => '',
                    ]],
                    ['type' => 'features', 'data' => [
                        'heading' => 'What we do',
                        'items' => [
                            ['icon' => '🎯', 'title' => 'Strategy', 'text' => 'Clear positioning and a plan that fits your business.'],
                            ['icon' => '🛠️', 'title' => 'Build', 'text' => 'Fast, considered sites on a CMS you actually own.'],
                            ['icon' => '🔁', 'title' => 'Iterate', 'text' => 'Easy updates whenever your needs change.'],
                        ],
                    ]],
                    ['type' => 'cta', 'data' => [
                        'heading' => 'Ready to start?',
                        'text' => 'Tell us what you have in mind and we will take it from there.',
                        'button_label' => 'Say hello',
                        'button_url' => '#contact',
                    ]],
                    ['type' => 'form', 'data' => ['slug' => $form->slug]],
                ],
            ],
        );
    }
}
