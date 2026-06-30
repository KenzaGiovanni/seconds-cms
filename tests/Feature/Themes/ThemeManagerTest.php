<?php

use App\Models\Theme;
use App\Support\ThemeManager;
use App\Support\ThemeManifest;

beforeEach(function () {
    $this->manager = app(ThemeManager::class);
});

// -- Manifest parsing --

it('parses a valid theme.json', function () {
    $manifest = ThemeManifest::fromArray([
        'slug' => 'default',
        'name' => 'Seconds Default',
        'version' => '1.0.0',
        'author' => '&Now',
        'supports' => ['content', 'ecommerce'],
        'settings' => [],
    ]);

    expect($manifest->slug)->toBe('default')
        ->and($manifest->name)->toBe('Seconds Default')
        ->and($manifest->supports)->toContain('ecommerce');
});

it('throws when theme.json is missing required fields', function () {
    ThemeManifest::fromArray(['name' => 'No Slug']);
})->throws(InvalidArgumentException::class);

it('parses theme.json from disk', function () {
    $manifest = ThemeManifest::fromPath($this->manager->themesPath('default'));

    expect($manifest->slug)->toBe('default')
        ->and($manifest->name)->toBe('Seconds Default');
});

// -- Install --

it('installs a theme from a path', function () {
    $theme = $this->manager->install($this->manager->themesPath('default'));

    expect($theme->slug)->toBe('default')
        ->and($theme->status)->toBe('installed')
        ->and(Theme::count())->toBe(1);
});

it('install is idempotent - does not duplicate rows', function () {
    $this->manager->install($this->manager->themesPath('default'));
    $this->manager->install($this->manager->themesPath('default'));

    expect(Theme::count())->toBe(1);
});

// -- Activate / deactivate --

it('activating a theme marks it active and deactivates others', function () {
    $themeA = Theme::create(['slug' => 'alpha', 'name' => 'Alpha', 'status' => 'active', 'installed_at' => now()]);
    $themeB = Theme::create(['slug' => 'beta', 'name' => 'Beta', 'status' => 'installed', 'installed_at' => now()]);

    $this->manager->activate($themeB);

    expect($themeB->fresh()->status)->toBe('active')
        ->and($themeA->fresh()->status)->toBe('installed');
});

it('only one theme is active after multiple activations', function () {
    $a = Theme::create(['slug' => 'a', 'name' => 'A', 'status' => 'installed', 'installed_at' => now()]);
    $b = Theme::create(['slug' => 'b', 'name' => 'B', 'status' => 'installed', 'installed_at' => now()]);
    $c = Theme::create(['slug' => 'c', 'name' => 'C', 'status' => 'installed', 'installed_at' => now()]);

    $this->manager->activate($a);
    $this->manager->activate($c);

    expect(Theme::where('status', 'active')->count())->toBe(1)
        ->and($c->fresh()->status)->toBe('active');
});

// -- Uninstall --

it('can uninstall an inactive theme', function () {
    $theme = Theme::create(['slug' => 'removable', 'name' => 'Removable', 'status' => 'installed', 'installed_at' => now()]);

    $this->manager->uninstall($theme);

    expect(Theme::find($theme->id))->toBeNull();
});

it('cannot uninstall the active theme', function () {
    $theme = Theme::create(['slug' => 'active-one', 'name' => 'Active', 'status' => 'active', 'installed_at' => now()]);

    $this->manager->uninstall($theme);
})->throws(RuntimeException::class, 'Cannot uninstall the active theme');

// -- View namespace --

it('active() returns the active theme', function () {
    Theme::create(['slug' => 'other', 'name' => 'Other', 'status' => 'installed', 'installed_at' => now()]);
    $active = Theme::create(['slug' => 'main', 'name' => 'Main', 'status' => 'active', 'installed_at' => now()]);

    expect($this->manager->active()?->slug)->toBe('main');
});

it('boot registers the active theme view namespace', function () {
    Theme::create(['slug' => 'default', 'name' => 'Default', 'status' => 'active', 'installed_at' => now()]);

    $this->manager->boot();

    $hints = app('view')->getFinder()->getHints();
    $viewPath = $this->manager->themesPath('default').'/views';

    expect(isset($hints['theme']))->toBeTrue()
        ->and(in_array($viewPath, $hints['theme']))->toBeTrue();
});
