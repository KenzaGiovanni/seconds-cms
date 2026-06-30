<?php

/**
 * Block definitions for the Seconds Default theme.
 *
 * Each entry maps a block `type` to its label + field schema. The admin block
 * editor generates an input form from these fields; the matching Blade partial
 * at `views/blocks/<type>.blade.php` renders the stored data on the front-end.
 *
 * Field types: text, textarea, richtext, image, number, toggle, select, repeater.
 * A `repeater` carries nested `fields` (e.g. the feature grid's cards).
 *
 * This file is the reference for theme authors: to add a block, declare it here
 * and ship a partial of the same name.
 */

return [
    'paragraph' => [
        'label' => 'Paragraph',
        'fields' => [
            ['key' => 'text', 'type' => 'textarea', 'label' => 'Text'],
        ],
    ],

    'heading' => [
        'label' => 'Heading',
        'fields' => [
            ['key' => 'level', 'type' => 'select', 'label' => 'Level', 'default' => '2',
                'options' => ['2' => 'H2', '3' => 'H3', '4' => 'H4']],
            ['key' => 'text', 'type' => 'text', 'label' => 'Text'],
        ],
    ],

    'image' => [
        'label' => 'Image',
        'fields' => [
            ['key' => 'url', 'type' => 'image', 'label' => 'Image'],
            ['key' => 'alt', 'type' => 'text', 'label' => 'Alt text'],
            ['key' => 'caption', 'type' => 'text', 'label' => 'Caption'],
        ],
    ],

    'features' => [
        'label' => 'Feature grid',
        'fields' => [
            ['key' => 'heading', 'type' => 'text', 'label' => 'Section heading'],
            ['key' => 'items', 'type' => 'repeater', 'label' => 'Features', 'fields' => [
                ['key' => 'title', 'type' => 'text', 'label' => 'Title'],
                ['key' => 'text', 'type' => 'textarea', 'label' => 'Description'],
            ]],
        ],
    ],

    'form' => [
        'label' => 'Form',
        'fields' => [
            ['key' => 'slug', 'type' => 'text', 'label' => 'Form slug',
                'help' => 'The slug of the form to embed (see Forms).'],
        ],
    ],

    'divider' => [
        'label' => 'Divider',
        'fields' => [],
    ],
];
