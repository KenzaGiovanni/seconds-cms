<?php

/**
 * Block definitions for the Seconds Default theme.
 *
 * Each entry maps a block `type` to its label + field schema. The admin block
 * editor generates an input form from these fields; the matching Blade partial
 * at `views/blocks/<type>.blade.php` renders the stored data on the front-end.
 *
 * Field types: text, textarea, richtext, email, image, number, toggle, select,
 * repeater. A `repeater` carries nested `fields` (e.g. the feature grid cards).
 *
 * This file is the reference for theme authors: to add a block, declare it here
 * and ship a partial of the same name. Order here is the order in the picker.
 */

return [
    // -- Text blocks --
    'paragraph' => [
        'label' => 'Paragraph',
        'fields' => [
            ['key' => 'text', 'type' => 'textarea', 'label' => 'Text'],
        ],
    ],

    'richtext' => [
        'label' => 'Rich text',
        'fields' => [
            ['key' => 'html', 'type' => 'richtext', 'label' => 'Content', 'help' => 'HTML is rendered as-is.'],
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

    // -- Section blocks --
    'hero' => [
        'label' => 'Hero',
        'fields' => [
            ['key' => 'heading', 'type' => 'text', 'label' => 'Heading'],
            ['key' => 'subheading', 'type' => 'textarea', 'label' => 'Subheading'],
            ['key' => 'cta_label', 'type' => 'text', 'label' => 'Button label'],
            ['key' => 'cta_url', 'type' => 'text', 'label' => 'Button URL'],
            ['key' => 'image', 'type' => 'image', 'label' => 'Background image', 'help' => 'Optional - sits behind the text.'],
        ],
    ],

    'features' => [
        'label' => 'Feature grid',
        'fields' => [
            ['key' => 'heading', 'type' => 'text', 'label' => 'Section heading'],
            ['key' => 'items', 'type' => 'repeater', 'label' => 'Features', 'fields' => [
                ['key' => 'icon', 'type' => 'text', 'label' => 'Icon', 'help' => 'An emoji or short symbol.'],
                ['key' => 'title', 'type' => 'text', 'label' => 'Title'],
                ['key' => 'text', 'type' => 'textarea', 'label' => 'Description'],
            ]],
        ],
    ],

    'gallery' => [
        'label' => 'Gallery',
        'fields' => [
            ['key' => 'items', 'type' => 'repeater', 'label' => 'Images', 'fields' => [
                ['key' => 'url', 'type' => 'image', 'label' => 'Image'],
                ['key' => 'caption', 'type' => 'text', 'label' => 'Caption'],
            ]],
        ],
    ],

    'cta' => [
        'label' => 'Call to action',
        'fields' => [
            ['key' => 'heading', 'type' => 'text', 'label' => 'Heading'],
            ['key' => 'text', 'type' => 'textarea', 'label' => 'Text'],
            ['key' => 'button_label', 'type' => 'text', 'label' => 'Button label'],
            ['key' => 'button_url', 'type' => 'text', 'label' => 'Button URL'],
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

    'form' => [
        'label' => 'Form',
        'fields' => [
            ['key' => 'slug', 'type' => 'text', 'label' => 'Form slug',
                'help' => 'The slug of the form to embed (see Forms).'],
        ],
    ],

    'testimonials' => [
        'label' => 'Testimonials',
        'fields' => [
            ['key' => 'heading', 'type' => 'text', 'label' => 'Section heading'],
            ['key' => 'items', 'type' => 'repeater', 'label' => 'Testimonials', 'fields' => [
                ['key' => 'quote', 'type' => 'textarea', 'label' => 'Quote'],
                ['key' => 'name', 'type' => 'text', 'label' => 'Name'],
                ['key' => 'role', 'type' => 'text', 'label' => 'Role / Company'],
            ]],
        ],
    ],

    'divider' => [
        'label' => 'Divider',
        'fields' => [],
    ],
];
