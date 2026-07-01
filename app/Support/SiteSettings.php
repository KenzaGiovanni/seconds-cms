<?php

namespace App\Support;

use App\Models\Page;
use App\Models\Setting;

/**
 * Typed accessors over the site-level (WordPress "General/Reading") settings
 * stored in the `settings` table. Distinct from ThemeSettings, which holds
 * per-theme design tokens (theme.json). Site settings survive theme switches.
 */
class SiteSettings
{
    public static function siteName(): string
    {
        return (string) Setting::get('site_name', config('app.name'));
    }

    public static function timezone(): string
    {
        return (string) (Setting::get('timezone') ?: 'UTC');
    }

    public static function dateFormat(): string
    {
        return (string) (Setting::get('date_format') ?: 'd M Y');
    }

    public static function postsPerPage(): int
    {
        return max(1, (int) (Setting::get('posts_per_page') ?: 10));
    }

    /** 'posts' (blog feed) or 'page' (a static front page). */
    public static function showOnFront(): string
    {
        return Setting::get('show_on_front', 'posts') === 'page' ? 'page' : 'posts';
    }

    public static function frontPageId(): ?int
    {
        $id = Setting::get('front_page_id');

        return $id ? (int) $id : null;
    }

    public static function isFrontPage(int $pageId): bool
    {
        return self::showOnFront() === 'page' && self::frontPageId() === $pageId;
    }

    /**
     * The published static front page, or null when the site shows the blog feed
     * (or the configured page is missing / unpublished).
     */
    public static function frontPage(): ?Page
    {
        if (self::showOnFront() !== 'page') {
            return null;
        }

        $id = self::frontPageId();

        return $id ? Page::published()->with('featuredImage')->find($id) : null;
    }

    /** Set (or clear, with null) the static front page. */
    public static function setFrontPage(?int $pageId): void
    {
        if ($pageId) {
            Setting::set('show_on_front', 'page');
            Setting::set('front_page_id', (string) $pageId);
        } else {
            Setting::set('show_on_front', 'posts');
            Setting::set('front_page_id', '');
        }
    }

    /** Whether the in-admin theme code editor is turned on for this install. */
    public static function themeEditorEnabled(): bool
    {
        return filter_var(Setting::get('theme_editor_enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    public static function setThemeEditor(bool $enabled): void
    {
        Setting::set('theme_editor_enabled', $enabled ? 'true' : 'false');
    }
}
