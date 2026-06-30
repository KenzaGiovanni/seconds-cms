<?php

use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Page;
use App\Models\Post;
use App\Models\Theme;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

// -- Access --

it('blocks guests from theme settings', function () {
    $this->get('/admin/themes/settings')->assertRedirect('/admin/login');
});

it('blocks editors (no themes.manage) from theme settings', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Editor->value);

    actingAs($user)->get('/admin/themes/settings')->assertForbidden();
});

it('allows an admin to access theme settings', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    actingAs($user)->get('/admin/themes/settings')->assertOk();
});

// -- Save settings --

it('saves theme settings and persists them', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $theme = Theme::create([
        'slug' => 'default',
        'name' => 'Seconds Default',
        'status' => 'active',
        'settings' => [],
        'installed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Themes\ThemeSettings::class)
        ->set('settings.primary_color', '#FF0000')
        ->set('settings.show_hero', false)
        ->call('save');

    $saved = $theme->fresh()->settings;
    expect($saved['primary_color'])->toBe('#FF0000')
        ->and($saved['show_hero'])->toBe(false);
});

it('saved theme settings render on the front-end', function () {
    Theme::create([
        'slug' => 'default',
        'name' => 'Seconds Default',
        'status' => 'active',
        'settings' => ['primary_color' => '#ABCDEF', 'show_hero' => true],
        'installed_at' => now(),
    ]);

    Page::create(['title' => 'Home Page', 'slug' => 'home-page', 'status' => ContentStatus::Published, 'published_at' => now()->subHour()]);

    get('/home-page')->assertOk()->assertSee('#ABCDEF');
});

// -- Block editor --

it('saves blocks on a page and they render on the front-end', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Content\PageForm::class)
        ->set('title', 'Block Test')
        ->set('slug', 'block-test')
        ->set('status', 'published')
        ->set('publishedAt', now()->subHour()->format('Y-m-d\TH:i'))
        ->call('addBlock')                                    // adds paragraph
        ->set('blocks.0.data.text', 'Hello from a block.')
        ->call('save');

    get('/block-test')
        ->assertOk()
        ->assertSee('Hello from a block.');
});

it('reorders blocks on save', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $page = Page::create([
        'title' => 'Reorder',
        'slug' => 'reorder',
        'status' => ContentStatus::Draft,
        'blocks' => [
            ['type' => 'paragraph', 'data' => ['text' => 'First']],
            ['type' => 'paragraph', 'data' => ['text' => 'Second']],
        ],
    ]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Content\PageForm::class, ['id' => $page->id])
        ->call('moveBlockDown', 0)  // swap First and Second
        ->call('save');

    $updated = Page::find($page->id)->blocks;
    expect($updated[0]['data']['text'])->toBe('Second')
        ->and($updated[1]['data']['text'])->toBe('First');
});

it('removes a block', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Content\PageForm::class)
        ->set('title', 'Remove Block')
        ->set('slug', 'remove-block')
        ->set('status', 'draft')
        ->call('addBlock')
        ->call('addBlock')
        ->call('removeBlock', 0)
        ->assertCount('blocks', 1);
});

it('saves blocks on a post', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Content\PostForm::class)
        ->set('title', 'Post With Block')
        ->set('slug', 'post-with-block')
        ->set('status', 'published')
        ->set('publishedAt', now()->subHour()->format('Y-m-d\TH:i'))
        ->call('addBlock')
        ->set('blocks.0.data.text', 'Post block content.')
        ->call('save');

    get('/blog/post-with-block')
        ->assertOk()
        ->assertSee('Post block content.');
});
