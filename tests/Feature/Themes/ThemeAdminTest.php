<?php

use App\Enums\Role;
use App\Models\Theme;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

// -- Access control --

it('blocks guests from the themes admin screen', function () {
    $this->get('/admin/themes')->assertRedirect('/admin/login');
});

it('blocks editors from themes admin', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Editor->value);

    actingAs($user)->get('/admin/themes')->assertForbidden();
});

it('allows an admin to access themes admin', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    actingAs($user)->get('/admin/themes')->assertOk();
});

// -- Install via ZIP --

function makeThemeZipFile(string $slug = 'test-theme', string $name = 'Test Theme'): UploadedFile
{
    $zipPath = sys_get_temp_dir() . '/' . $slug . '-' . uniqid() . '.zip';

    $zip = new \ZipArchive;
    $zip->open($zipPath, \ZipArchive::CREATE);
    $zip->addFromString($slug . '/theme.json', json_encode([
        'name'    => $name,
        'slug'    => $slug,
        'version' => '1.0.0',
        'author'  => 'Test Author',
    ]));
    $zip->close();

    return UploadedFile::fake()->createWithContent($slug . '.zip', file_get_contents($zipPath));
}

it('installs a theme from a valid ZIP', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $zip = makeThemeZipFile('my-theme', 'My Theme');

    Livewire::actingAs($user)
        ->test(\App\Livewire\Themes\ThemeAdmin::class)
        ->set('zipFile', $zip)
        ->call('install');

    expect(Theme::where('slug', 'my-theme')->exists())->toBeTrue();

    // Clean up installed theme dir.
    $dest = app(\App\Support\ThemeManager::class)->themesPath('my-theme');
    if (is_dir($dest)) {
        File::deleteDirectory($dest);
    }
});

it('rejects a ZIP with no theme.json', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $countBefore = Theme::count();

    $zipPath = sys_get_temp_dir() . '/empty-' . uniqid() . '.zip';
    $zip = new \ZipArchive;
    $zip->open($zipPath, \ZipArchive::CREATE);
    $zip->addFromString('readme.txt', 'no theme here');
    $zip->close();

    $file = UploadedFile::fake()->createWithContent('empty.zip', file_get_contents($zipPath));

    Livewire::actingAs($user)
        ->test(\App\Livewire\Themes\ThemeAdmin::class)
        ->set('zipFile', $file)
        ->call('install');

    // No new theme should have been created.
    expect(Theme::count())->toBe($countBefore);
});

// -- Activate --

it('activates an installed theme', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $active = Theme::create([
        'slug'         => 'default',
        'name'         => 'Default',
        'status'       => 'active',
        'settings'     => [],
        'installed_at' => now(),
    ]);

    $other = Theme::create([
        'slug'         => 'other-theme',
        'name'         => 'Other Theme',
        'status'       => 'installed',
        'settings'     => [],
        'installed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Themes\ThemeAdmin::class)
        ->call('activate', $other->id);

    expect($other->fresh()->status)->toBe('active')
        ->and($active->fresh()->status)->toBe('installed');
});

// -- Uninstall --

it('cannot uninstall the active theme', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $theme = Theme::create([
        'slug'         => 'default',
        'name'         => 'Default',
        'status'       => 'active',
        'settings'     => [],
        'installed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Themes\ThemeAdmin::class)
        ->call('uninstall', $theme->id);

    // Active theme must survive the attempt.
    expect(Theme::find($theme->id))->not->toBeNull()
        ->and(Theme::find($theme->id)->status)->toBe('active');
});

it('uninstalls a non-active theme and removes it from the DB', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Theme::create([
        'slug'         => 'default',
        'name'         => 'Default',
        'status'       => 'active',
        'settings'     => [],
        'installed_at' => now(),
    ]);

    $other = Theme::create([
        'slug'         => 'removable',
        'name'         => 'Removable',
        'status'       => 'installed',
        'settings'     => [],
        'installed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Themes\ThemeAdmin::class)
        ->call('uninstall', $other->id);

    expect(Theme::find($other->id))->toBeNull();
});

it('screen reflects state after activate', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Theme::create([
        'slug'         => 'default',
        'name'         => 'Default',
        'status'       => 'active',
        'settings'     => [],
        'installed_at' => now(),
    ]);

    $other = Theme::create([
        'slug'         => 'new-theme',
        'name'         => 'New Theme',
        'status'       => 'installed',
        'settings'     => [],
        'installed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Themes\ThemeAdmin::class)
        ->call('activate', $other->id)
        ->assertSee('New Theme');
});
