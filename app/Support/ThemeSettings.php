<?php

namespace App\Support;

use App\Models\Theme;

/**
 * Resolves a theme's effective settings: the defaults declared in its
 * theme.json `settings` schema, merged with any stored overrides
 * (themes.settings). Stored values win.
 */
class ThemeSettings
{
    public function __construct(private ThemeManager $manager) {}

    public function for(Theme $theme): array
    {
        $defaults = [];

        $manifestPath = $this->manager->themesPath($theme->slug);

        if (is_dir($manifestPath)) {
            $defaults = ThemeManifest::fromPath($manifestPath)->settings;
        }

        return array_merge($defaults, $theme->settings ?? []);
    }

    /** Effective settings for the active theme (empty if none active). */
    public function active(): array
    {
        $theme = Theme::active();

        return $theme ? $this->for($theme) : [];
    }
}
