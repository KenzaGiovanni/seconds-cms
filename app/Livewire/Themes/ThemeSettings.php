<?php

namespace App\Livewire\Themes;

use App\Enums\Permission;
use App\Models\Theme;
use App\Support\ThemeManager;
use App\Support\ThemeManifest;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Theme Settings')]
class ThemeSettings extends Component
{
    /** Current setting values keyed by setting key. */
    public array $settings = [];

    /** Schema from theme.json (keyed by key, value = default). */
    public array $schema = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can(Permission::ThemesManage->value), 403);

        $theme = Theme::active();

        if (! $theme) {
            return;
        }

        $manager = app(ThemeManager::class);
        $path = $manager->themesPath($theme->slug);
        $manifest = is_dir($path) ? ThemeManifest::fromPath($path) : null;

        $this->schema = $manifest?->settings ?? [];
        $stored = $theme->settings ?? [];

        // Populate settings with stored values (falling back to schema defaults).
        foreach ($this->schema as $key => $default) {
            $this->settings[$key] = $stored[$key] ?? $default;
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can(Permission::ThemesManage->value), 403);

        $theme = Theme::active();

        if (! $theme) {
            session()->flash('error', 'No active theme to save settings for.');

            return;
        }

        // Cast bool-schema values back to bool (HTML sends '1'/'0' or 'true').
        $normalized = [];
        foreach ($this->schema as $key => $default) {
            $value = $this->settings[$key] ?? $default;
            $normalized[$key] = is_bool($default) ? (bool) $value : $value;
        }

        $theme->update(['settings' => $normalized]);
        session()->flash('success', 'Theme settings saved.');
    }

    public function render()
    {
        return view('livewire.themes.theme-settings', [
            'activeTheme' => Theme::active(),
        ]);
    }
}
