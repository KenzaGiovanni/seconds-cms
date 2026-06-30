<?php

use App\Models\Theme;
use App\Support\BlockRegistry;

beforeEach(function () {
    Theme::updateOrCreate(
        ['slug' => 'default'],
        ['name' => 'Seconds Default', 'status' => 'active', 'settings' => [], 'installed_at' => now()],
    );
    app(BlockRegistry::class)->forget();
});

it('reads the active theme block definitions', function () {
    $registry = app(BlockRegistry::class);

    expect($registry->has('heading'))->toBeTrue()
        ->and($registry->has('paragraph'))->toBeTrue()
        ->and($registry->has('image'))->toBeTrue()
        ->and($registry->has('divider'))->toBeTrue();
});

it('returns a block definition with its fields', function () {
    $heading = app(BlockRegistry::class)->get('heading');

    expect($heading)->not->toBeNull()
        ->and($heading->label)->toBe('Heading')
        ->and($heading->field('level'))->not->toBeNull()
        ->and($heading->field('level')->type)->toBe('select')
        ->and($heading->field('text'))->not->toBeNull();
});

it('seeds default data from the definition', function () {
    $data = app(BlockRegistry::class)->get('heading')->defaultData();

    expect($data)->toBe(['level' => '2', 'text' => '']);
});

it('returns null for an unknown block type', function () {
    $registry = app(BlockRegistry::class);

    expect($registry->has('nope'))->toBeFalse()
        ->and($registry->get('nope'))->toBeNull();
});

it('exposes a type => label option map for the editor', function () {
    $options = app(BlockRegistry::class)->options();

    expect($options)->toHaveKey('heading', 'Heading')
        ->and($options)->toHaveKey('paragraph', 'Paragraph')
        ->and($options)->toHaveKey('divider', 'Divider');
});
