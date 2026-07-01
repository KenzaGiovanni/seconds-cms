<?php

use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Livewire\Settings\WebsiteSettings;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);
    seed(SettingsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Setting::flushCache();
});

function websiteAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    return $user;
}

it('blocks editors from website settings', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    actingAs($editor)->get('/admin/settings')->assertForbidden();
});

it('lets an admin open website settings', function () {
    actingAs(websiteAdmin())->get('/admin/settings')->assertOk();
});

it('persists general and reading settings', function () {
    Livewire::actingAs(websiteAdmin())
        ->test(WebsiteSettings::class)
        ->set('siteName', 'Toko Kenza')
        ->set('siteTagline', 'Barang bagus')
        ->set('timezone', 'Asia/Jakarta')
        ->set('postsPerPage', 6)
        ->set('showOnFront', 'posts')
        ->call('save')
        ->assertHasNoErrors();

    Setting::flushCache();
    expect(Setting::get('site_name'))->toBe('Toko Kenza')
        ->and(Setting::get('timezone'))->toBe('Asia/Jakarta')
        ->and(Setting::get('posts_per_page'))->toBe('6');
});

it('validates the timezone', function () {
    Livewire::actingAs(websiteAdmin())
        ->test(WebsiteSettings::class)
        ->set('timezone', 'Mars/Olympus')
        ->call('save')
        ->assertHasErrors(['timezone']);
});

it('setting a static page as front page renders it at the root', function () {
    $page = Page::create([
        'title' => 'Welcome Home',
        'slug' => 'welcome-home',
        'status' => ContentStatus::Published,
        'published_at' => now()->subHour(),
        'blocks' => [
            ['type' => 'hero', 'data' => ['heading' => 'Homepage via settings']],
        ],
    ]);

    Livewire::actingAs(websiteAdmin())
        ->test(WebsiteSettings::class)
        ->set('showOnFront', 'page')
        ->set('frontPageId', $page->id)
        ->call('save')
        ->assertHasNoErrors();

    Setting::flushCache();
    actingAs(websiteAdmin())->get('/')->assertOk()->assertSee('Homepage via settings');
});
