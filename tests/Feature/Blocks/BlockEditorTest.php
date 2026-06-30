<?php

use App\Enums\Role;
use App\Livewire\Content\PageForm;
use App\Models\Theme;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\get;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Theme::updateOrCreate(
        ['slug' => 'default'],
        ['name' => 'Seconds Default', 'status' => 'active', 'settings' => [], 'installed_at' => now()],
    );

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::Admin->value);
});

it('adds the first theme block type by default', function () {
    $component = Livewire::actingAs($this->admin)->test(PageForm::class)->call('addBlock');

    expect($component->get('blocks'))->toHaveCount(1)
        ->and($component->get('blocks')[0]['type'])->toBe('paragraph')
        ->and($component->get('blocks')[0]['data'])->toBe(['text' => '']);
});

it('seeds a chosen block with its schema default data', function () {
    $component = Livewire::actingAs($this->admin)->test(PageForm::class)
        ->set('newBlockType', 'heading')
        ->call('addBlock');

    expect($component->get('blocks')[0]['data'])->toBe(['level' => '2', 'text' => '']);
});

it('builds a repeater block and renders it end to end', function () {
    Livewire::actingAs($this->admin)->test(PageForm::class)
        ->set('title', 'Services')
        ->set('slug', 'services')
        ->set('status', 'published')
        ->set('publishedAt', now()->subHour()->format('Y-m-d\TH:i'))
        ->set('newBlockType', 'features')
        ->call('addBlock')
        ->call('addRepeaterItem', 0, 'items')
        ->call('addRepeaterItem', 0, 'items')
        ->set('blocks.0.data.heading', 'What we do')
        ->set('blocks.0.data.items.0.title', 'Consulting')
        ->set('blocks.0.data.items.0.text', 'We advise.')
        ->set('blocks.0.data.items.1.title', 'Support')
        ->call('save');

    get('/services')
        ->assertOk()
        ->assertSee('What we do')
        ->assertSee('Consulting')
        ->assertSee('We advise.')
        ->assertSee('Support');
});

it('removes a repeater item', function () {
    $component = Livewire::actingAs($this->admin)->test(PageForm::class)
        ->set('newBlockType', 'features')
        ->call('addBlock')
        ->call('addRepeaterItem', 0, 'items')
        ->call('addRepeaterItem', 0, 'items')
        ->call('removeRepeaterItem', 0, 'items', 0);

    expect($component->get('blocks')[0]['data']['items'])->toHaveCount(1);
});

it('persists a select field value and renders it', function () {
    Livewire::actingAs($this->admin)->test(PageForm::class)
        ->set('title', 'Heading Test')
        ->set('slug', 'heading-test')
        ->set('status', 'published')
        ->set('publishedAt', now()->subHour()->format('Y-m-d\TH:i'))
        ->set('newBlockType', 'heading')
        ->call('addBlock')
        ->set('blocks.0.data.text', 'Big Title')
        ->set('blocks.0.data.level', '3')
        ->call('save');

    get('/heading-test')->assertOk()->assertSee('<h3>Big Title</h3>', false);
});

it('routes a media pick into a targeted block image field', function () {
    $component = Livewire::actingAs($this->admin)->test(PageForm::class)
        ->set('newBlockType', 'image')
        ->call('addBlock')
        ->call('chooseBlockMedia', '0.data.url')
        ->call('onMediaSelected', 99, 'http://example.com/photo.jpg');

    expect($component->get('blocks')[0]['data']['url'])->toBe('http://example.com/photo.jpg')
        ->and($component->get('mediaTargetPath'))->toBeNull()
        ->and($component->get('featuredImageId'))->toBeNull();
});

it('still routes a media pick to the featured image when no block target is set', function () {
    $component = Livewire::actingAs($this->admin)->test(PageForm::class)
        ->call('onMediaSelected', 5, 'http://example.com/featured.jpg');

    expect($component->get('featuredImageId'))->toBe(5)
        ->and($component->get('featuredImageUrl'))->toBe('http://example.com/featured.jpg');
});
