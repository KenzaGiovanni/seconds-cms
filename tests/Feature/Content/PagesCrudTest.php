<?php

use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Livewire\Content\PageForm;
use App\Livewire\Content\PageList;
use App\Models\Content;
use App\Models\Page;
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

// -- Access control --

it('blocks guests from pages admin', function () {
    $this->get('/admin/pages')->assertRedirect('/admin/login');
});

it('blocks editors without content.manage from pages admin', function () {
    // Editor has content.manage — check that a roleless user can't access
    $user = User::factory()->create(); // no role = no staff = 403
    actingAs($user)->get('/admin/pages')->assertForbidden();
});

it('allows an editor (content.manage) to access pages list', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Editor->value);

    actingAs($user)->get('/admin/pages')->assertOk();
});

// -- Pages list --

it('shows pages in the list', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Page::create(['title' => 'About Us', 'slug' => 'about-us', 'status' => ContentStatus::Published, 'published_at' => now()->subHour()]);
    Page::create(['title' => 'Contact', 'slug' => 'contact', 'status' => ContentStatus::Draft]);

    actingAs($user)
        ->get('/admin/pages')
        ->assertOk()
        ->assertSee('About Us')
        ->assertSee('Contact');
});

// -- Create --

it('creates a page and redirects to the list', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(PageForm::class)
        ->set('title', 'Our Story')
        ->set('slug', 'our-story')
        ->set('body', 'We started in 2020.')
        ->set('status', 'draft')
        ->call('save')
        ->assertRedirect(route('admin.pages.index'));

    expect(Page::where('slug', 'our-story')->exists())->toBeTrue()
        ->and(Page::first()->title)->toBe('Our Story');
});

it('auto-generates slug from title when slug is not manually edited', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(PageForm::class)
        ->set('title', 'Hello World')
        ->assertSet('slug', 'hello-world');
});

it('preserves manually edited slug when title changes', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(PageForm::class)
        ->set('title', 'Hello World')
        ->set('slug', 'custom-slug')  // manual edit marks slugManuallyEdited = true
        ->set('title', 'New Title')   // should NOT overwrite custom-slug
        ->assertSet('slug', 'custom-slug');
});

it('rejects a duplicate slug', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Page::create(['title' => 'Existing', 'slug' => 'existing', 'status' => ContentStatus::Draft]);

    Livewire::actingAs($user)
        ->test(PageForm::class)
        ->set('title', 'Other Page')
        ->set('slug', 'existing')
        ->set('status', 'draft')
        ->call('save')
        ->assertHasErrors(['slug']);
});

it('requires a title', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(PageForm::class)
        ->set('title', '')
        ->set('slug', 'no-title')
        ->set('status', 'draft')
        ->call('save')
        ->assertHasErrors(['title']);
});

// -- Edit --

it('loads an existing page for editing', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $page = Page::create(['title' => 'Team', 'slug' => 'team', 'status' => ContentStatus::Draft, 'body' => 'We are a team.']);

    Livewire::actingAs($user)
        ->test(PageForm::class, ['id' => $page->id])
        ->assertSet('title', 'Team')
        ->assertSet('slug', 'team')
        ->assertSet('body', 'We are a team.');
});

it('updates an existing page', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $page = Page::create(['title' => 'Old Title', 'slug' => 'old-title', 'status' => ContentStatus::Draft]);

    Livewire::actingAs($user)
        ->test(PageForm::class, ['id' => $page->id])
        ->set('title', 'New Title')
        ->set('slug', 'new-title')
        ->call('save')
        ->assertRedirect(route('admin.pages.index'));

    expect($page->fresh()->title)->toBe('New Title')
        ->and($page->fresh()->slug)->toBe('new-title');
});

it('allows editing a page without changing its slug (same slug is OK)', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $page = Page::create(['title' => 'Services', 'slug' => 'services', 'status' => ContentStatus::Draft]);

    Livewire::actingAs($user)
        ->test(PageForm::class, ['id' => $page->id])
        ->set('title', 'Our Services')
        ->call('save')
        ->assertHasNoErrors();
});

// -- Delete --

it('deletes a page', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $page = Page::create(['title' => 'Delete Me', 'slug' => 'delete-me', 'status' => ContentStatus::Draft]);

    Livewire::actingAs($user)
        ->test(PageList::class)
        ->call('delete', $page->id);

    expect(Page::find($page->id))->toBeNull();
});

// -- Front-end rendering --

it('a published page renders on the front-end after creation', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(PageForm::class)
        ->set('title', 'Privacy Policy')
        ->set('slug', 'privacy')
        ->set('body', 'Your data is safe.')
        ->set('status', 'published')
        ->set('publishedAt', now()->subHour()->format('Y-m-d\TH:i'))
        ->call('save');

    $this->get('/privacy')
        ->assertOk()
        ->assertSee('Privacy Policy')
        ->assertSee('Your data is safe.');
});

// -- SEO fields --

it('saves SEO fields', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(PageForm::class)
        ->set('title', 'About')
        ->set('slug', 'about')
        ->set('status', 'draft')
        ->set('metaTitle', 'About - Seconds')
        ->set('metaDescription', 'Learn about us.')
        ->call('save');

    $page = Page::where('slug', 'about')->first();
    expect($page->meta_title)->toBe('About - Seconds')
        ->and($page->meta_description)->toBe('Learn about us.');
});
