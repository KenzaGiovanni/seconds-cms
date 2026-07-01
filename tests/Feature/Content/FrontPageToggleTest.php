<?php

use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Livewire\Content\PageForm;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use App\Support\SiteSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Setting::flushCache();
});

function contentAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    return $user;
}

it('sets a page as the front page from the editor', function () {
    $page = Page::create([
        'title' => 'Landing',
        'slug' => 'landing',
        'status' => ContentStatus::Published,
        'published_at' => now()->subHour(),
    ]);

    Livewire::actingAs(contentAdmin())
        ->test(PageForm::class, ['id' => $page->id])
        ->set('isFrontPage', true)
        ->call('save');

    Setting::flushCache();
    expect(SiteSettings::frontPageId())->toBe($page->id)
        ->and(SiteSettings::showOnFront())->toBe('page');
});

it('releases the front page when unchecked', function () {
    $page = Page::create([
        'title' => 'Landing',
        'slug' => 'landing',
        'status' => ContentStatus::Published,
        'published_at' => now()->subHour(),
    ]);
    SiteSettings::setFrontPage($page->id);
    Setting::flushCache();

    Livewire::actingAs(contentAdmin())
        ->test(PageForm::class, ['id' => $page->id])
        ->assertSet('isFrontPage', true)
        ->set('isFrontPage', false)
        ->call('save');

    Setting::flushCache();
    expect(SiteSettings::frontPageId())->toBeNull()
        ->and(SiteSettings::showOnFront())->toBe('posts');
});
