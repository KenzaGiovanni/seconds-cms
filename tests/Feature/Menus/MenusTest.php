<?php

use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

// -- Access --

it('blocks guests from menus admin', function () {
    $this->get('/admin/menus')->assertRedirect('/admin/login');
});

it('allows an editor to access menus list', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Editor->value);

    actingAs($user)->get('/admin/menus')->assertOk();
});

// -- Create --

it('creates a menu with a theme location', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Menus\MenuBuilder::class)
        ->set('name', 'Main Navigation')
        ->set('location', 'primary')
        ->call('saveMenu')
        ->assertRedirect(route('admin.menus.edit', 1));

    expect(Menu::where('name', 'Main Navigation')->where('location', 'primary')->exists())->toBeTrue();
});

it('rejects duplicate location on two menus', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Menu::create(['name' => 'First', 'location' => 'primary']);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Menus\MenuBuilder::class)
        ->set('name', 'Second')
        ->set('location', 'primary')
        ->call('saveMenu')
        ->assertHasErrors(['location']);
});

// -- Items --

it('adds a custom URL item to a menu', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $menu = Menu::create(['name' => 'Nav', 'location' => null]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Menus\MenuBuilder::class, ['id' => $menu->id])
        ->set('newLabel', 'Home')
        ->set('newLinkType', 'url')
        ->set('newUrl', '/')
        ->call('addItem');

    expect(MenuItem::where('menu_id', $menu->id)->where('label', 'Home')->where('url', '/')->exists())->toBeTrue();
});

it('adds a content-linked item to a menu', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $menu = Menu::create(['name' => 'Nav', 'location' => null]);
    $page = Page::create(['title' => 'About', 'slug' => 'about', 'status' => ContentStatus::Published, 'published_at' => now()->subHour()]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Menus\MenuBuilder::class, ['id' => $menu->id])
        ->set('newLabel', 'About')
        ->set('newLinkType', 'content')
        ->set('newContentId', $page->id)
        ->call('addItem');

    $item = MenuItem::where('menu_id', $menu->id)->where('label', 'About')->first();
    expect($item)->not->toBeNull()
        ->and($item->linkable_id)->toBe($page->id)
        ->and($item->linkable_type)->toBe(get_class($page));
});

it('adds a nested child item', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $menu = Menu::create(['name' => 'Nav', 'location' => null]);
    $parent = MenuItem::create(['menu_id' => $menu->id, 'label' => 'Parent', 'url' => '#', 'sort_order' => 0]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Menus\MenuBuilder::class, ['id' => $menu->id])
        ->set('newLabel', 'Child')
        ->set('newLinkType', 'url')
        ->set('newUrl', '/child')
        ->set('newParentId', $parent->id)
        ->call('addItem');

    expect(MenuItem::where('parent_id', $parent->id)->where('label', 'Child')->exists())->toBeTrue();
});

it('removes an item from a menu', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $menu = Menu::create(['name' => 'Nav', 'location' => null]);
    $item = MenuItem::create(['menu_id' => $menu->id, 'label' => 'Remove Me', 'url' => '/', 'sort_order' => 0]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Menus\MenuBuilder::class, ['id' => $menu->id])
        ->call('removeItem', $item->id);

    expect(MenuItem::find($item->id))->toBeNull();
});

// -- Resolved URL --

it('resolves a page item URL to the page route', function () {
    $page = Page::create(['title' => 'Services', 'slug' => 'services', 'status' => ContentStatus::Published, 'published_at' => now()->subHour()]);
    $menu = Menu::create(['name' => 'Nav', 'location' => null]);
    $item = MenuItem::create([
        'menu_id' => $menu->id,
        'label' => 'Services',
        'linkable_type' => get_class($page),
        'linkable_id' => $page->id,
        'sort_order' => 0,
    ]);

    expect($item->resolvedUrl())->toBe(route('content.show', 'services'));
});

it('resolves a post item URL to the blog route', function () {
    $post = Post::create(['title' => 'Hello', 'slug' => 'hello', 'status' => ContentStatus::Published, 'published_at' => now()->subHour()]);
    $menu = Menu::create(['name' => 'Nav', 'location' => null]);
    $item = MenuItem::create([
        'menu_id' => $menu->id,
        'label' => 'Hello',
        'linkable_type' => get_class($post),
        'linkable_id' => $post->id,
        'sort_order' => 0,
    ]);

    expect($item->resolvedUrl())->toBe(route('blog.show', 'hello'));
});

// -- forLocation --

it('retrieves a menu by theme location', function () {
    Menu::create(['name' => 'Primary Nav', 'location' => 'primary']);
    Menu::create(['name' => 'Footer', 'location' => 'footer']);

    expect(Menu::forLocation('primary')?->name)->toBe('Primary Nav')
        ->and(Menu::forLocation('footer')?->name)->toBe('Footer')
        ->and(Menu::forLocation('sidebar'))->toBeNull();
});

// -- Delete --

it('deletes a menu and cascades to items', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $menu = Menu::create(['name' => 'Nav', 'location' => null]);
    MenuItem::create(['menu_id' => $menu->id, 'label' => 'Item', 'url' => '/', 'sort_order' => 0]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Menus\MenuList::class)
        ->call('delete', $menu->id);

    expect(Menu::find($menu->id))->toBeNull()
        ->and(MenuItem::where('menu_id', $menu->id)->count())->toBe(0);
});
