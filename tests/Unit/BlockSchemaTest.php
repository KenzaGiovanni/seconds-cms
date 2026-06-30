<?php

use App\Support\BlockDefinition;
use App\Support\FieldSchema;

// -- FieldSchema --

it('parses a basic field', function () {
    $f = FieldSchema::fromArray(['key' => 'heading', 'type' => 'text', 'label' => 'Heading']);

    expect($f->key)->toBe('heading')
        ->and($f->type)->toBe('text')
        ->and($f->label)->toBe('Heading')
        ->and($f->default)->toBe('');
});

it('derives a label from the key when none is given', function () {
    $f = FieldSchema::fromArray(['key' => 'hero_heading', 'type' => 'text']);

    expect($f->label)->toBe('Hero Heading');
});

it('coerces an unknown type to text', function () {
    $f = FieldSchema::fromArray(['key' => 'x', 'type' => 'bogus']);

    expect($f->type)->toBe('text');
});

it('gives type-appropriate defaults', function () {
    expect(FieldSchema::fromArray(['key' => 't', 'type' => 'toggle'])->default)->toBeFalse()
        ->and(FieldSchema::fromArray(['key' => 'n', 'type' => 'number'])->default)->toBeNull()
        ->and(FieldSchema::fromArray(['key' => 'r', 'type' => 'repeater'])->default)->toBe([]);
});

it('honours an explicit default', function () {
    $f = FieldSchema::fromArray(['key' => 'level', 'type' => 'select', 'default' => '2',
        'options' => ['2' => 'H2', '3' => 'H3']]);

    expect($f->default)->toBe('2')
        ->and($f->options)->toBe(['2' => 'H2', '3' => 'H3']);
});

it('parses nested repeater fields and builds a row template', function () {
    $f = FieldSchema::fromArray([
        'key' => 'items',
        'type' => 'repeater',
        'label' => 'Services',
        'fields' => [
            ['key' => 'title', 'type' => 'text'],
            ['key' => 'body', 'type' => 'textarea'],
            ['key' => 'featured', 'type' => 'toggle'],
        ],
    ]);

    expect($f->type)->toBe('repeater')
        ->and($f->fields)->toHaveCount(3)
        ->and($f->defaultValue())->toBe([])
        ->and($f->rowTemplate())->toBe(['title' => '', 'body' => '', 'featured' => false]);
});

// -- BlockDefinition --

it('builds a block definition with default data', function () {
    $def = BlockDefinition::fromArray('hero', [
        'label' => 'Hero',
        'fields' => [
            ['key' => 'heading', 'type' => 'text'],
            ['key' => 'show_button', 'type' => 'toggle'],
            ['key' => 'cards', 'type' => 'repeater', 'fields' => [['key' => 'title', 'type' => 'text']]],
        ],
    ]);

    expect($def->type)->toBe('hero')
        ->and($def->label)->toBe('Hero')
        ->and($def->fields)->toHaveCount(3)
        ->and($def->defaultData())->toBe(['heading' => '', 'show_button' => false, 'cards' => []]);
});

it('looks up a field by key', function () {
    $def = BlockDefinition::fromArray('x', ['fields' => [['key' => 'a', 'type' => 'text']]]);

    expect($def->field('a'))->not->toBeNull()
        ->and($def->field('a')->key)->toBe('a')
        ->and($def->field('missing'))->toBeNull();
});

it('labels a block from its type when none is given', function () {
    $def = BlockDefinition::fromArray('call_to_action', ['fields' => []]);

    expect($def->label)->toBe('Call To Action');
});
