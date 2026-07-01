<?php

use App\Enums\Role;
use App\Livewire\Themes\ThemeAdmin;
use App\Livewire\Themes\ThemeCodeEditor;
use App\Models\Setting;
use App\Models\User;
use App\Support\SiteSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function developer(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Developer->value);

    return $user;
}

it('returns 404 when the editor is disabled', function () {
    SiteSettings::setThemeEditor(false);

    actingAs(developer())->get('/admin/themes/code')->assertNotFound();
});

it('blocks roles without edit_code even when enabled', function () {
    SiteSettings::setThemeEditor(true);

    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    actingAs($editor)->get('/admin/themes/code')->assertForbidden();
});

it('lets a developer open the editor when enabled', function () {
    SiteSettings::setThemeEditor(true);

    actingAs(developer())->get('/admin/themes/code')->assertOk();
});

it('reads and writes a theme file, keeping a backup', function () {
    SiteSettings::setThemeEditor(true);

    $relative = 'default/views/blocks/paragraph.blade.php';
    $abs = base_path('themes/'.$relative);
    $original = File::get($abs);

    try {
        Livewire::actingAs(developer())
            ->test(ThemeCodeEditor::class)
            ->call('selectFile', $relative)
            ->assertSet('currentFile', $relative)
            ->set('content', $original."\n{{-- edited --}}")
            ->call('save')
            ->assertHasNoErrors();

        expect(File::get($abs))->toContain('{{-- edited --}}')
            ->and(collect(File::files(storage_path('app/theme-backups')))
                ->contains(fn ($f) => str_contains($f->getFilename(), 'paragraph')))->toBeTrue();
    } finally {
        File::put($abs, $original);
    }
});

it('rejects path traversal outside the themes directory', function () {
    SiteSettings::setThemeEditor(true);

    Livewire::actingAs(developer())
        ->test(ThemeCodeEditor::class)
        ->call('selectFile', '../.env')
        ->assertSet('currentFile', null);
});

it('rejects a disallowed extension', function () {
    SiteSettings::setThemeEditor(true);

    // Even a real file inside themes/ is rejected if the extension is not whitelisted.
    Livewire::actingAs(developer())
        ->test(ThemeCodeEditor::class)
        ->call('selectFile', 'default/screenshot.png')
        ->assertSet('currentFile', null);
});

it('toggles the editor on and off from the themes admin', function () {
    expect(SiteSettings::themeEditorEnabled())->toBeFalse();

    Livewire::actingAs(developer())
        ->test(ThemeAdmin::class)
        ->call('promptEditorToggle')
        ->assertSet('confirmingEditorToggle', true)
        ->call('toggleThemeEditor')
        ->assertSet('confirmingEditorToggle', false);

    Setting::flushCache();
    expect(SiteSettings::themeEditorEnabled())->toBeTrue();

    Livewire::actingAs(developer())
        ->test(ThemeAdmin::class)
        ->call('toggleThemeEditor');

    Setting::flushCache();
    expect(SiteSettings::themeEditorEnabled())->toBeFalse();
});

it('does not let a plain admin toggle the editor', function () {
    $adminUser = User::factory()->create();
    $adminUser->assignRole(Role::Admin->value);

    Livewire::actingAs($adminUser)
        ->test(ThemeAdmin::class)
        ->call('toggleThemeEditor')
        ->assertForbidden();

    Setting::flushCache();
    expect(SiteSettings::themeEditorEnabled())->toBeFalse();
});
