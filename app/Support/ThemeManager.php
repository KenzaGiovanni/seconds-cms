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
     * Register the active theme's Blade view namespace so storefront templates
     * resolve from themes/<slug>/views, with fallback to themes/default/views.
     */
    public function boot(): void
    {
        $active = $this->active();

        if (! $active) {
            return;
        }

        $viewPath = $this->themesPath($active->slug).'/views';

        if (is_dir($viewPath)) {
            View::prependNamespace('theme', $viewPath);
        }

        $fallbackPath = $this->themesPath('default').'/views';

        if (is_dir($fallbackPath)) {
            View::addNamespace('theme', $fallbackPath);
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
