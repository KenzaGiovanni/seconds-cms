<?php

namespace App\Support;

use App\Models\Theme;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use RuntimeException;

class ThemeManager
{
    public function themesPath(string $slug = ''): string
    {
        $base = base_path('themes');

        return $slug ? $base.'/'.$slug : $base;
    }

    public function active(): ?Theme
    {
        return Theme::active();
    }

    /**
     * Register the `theme::` Blade namespace. The default theme is always
     * registered as the base/fallback (there is always a theme), and the active
     * theme is prepended so its templates override the default.
     */
    public function boot(): void
    {
        // Base/fallback: the default theme is always available.
        $defaultPath = $this->themesPath('default').'/views';

        if (is_dir($defaultPath)) {
            View::addNamespace('theme', $defaultPath);
        }

        // Active theme overrides the default (skip if it *is* the default).
        $active = $this->active();

        if ($active && $active->slug !== 'default') {
            $activePath = $this->themesPath($active->slug).'/views';

            if (is_dir($activePath)) {
                View::prependNamespace('theme', $activePath);
            }
        }
    }

    public function install(string $themePath): Theme
    {
        $manifest = ThemeManifest::fromPath($themePath);

        return Theme::updateOrCreate(
            ['slug' => $manifest->slug],
            [
                'name' => $manifest->name,
                'version' => $manifest->version,
                'author' => $manifest->author,
                'screenshot' => $manifest->screenshot,
                'status' => 'installed',
                'installed_at' => now(),
            ]
        );
    }

    public function activate(Theme $theme): void
    {
        DB::transaction(function () use ($theme) {
            Theme::where('status', 'active')->update(['status' => 'installed']);
            $theme->update(['status' => 'active']);
        });
    }

    public function uninstall(Theme $theme): void
    {
        if ($theme->isActive()) {
            throw new RuntimeException('Cannot uninstall the active theme. Activate another theme first.');
        }

        $theme->delete();
    }
}
