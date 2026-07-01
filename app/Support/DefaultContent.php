<?php

namespace App\Support;

use App\Enums\ContentStatus;
use App\Models\Page;

/**
 * Ensures a fresh install always has a usable static home page (WordPress ships
 * a default front page you can later swap). Shared by the seeder and installer.
 */
class DefaultContent
{
    /**
     * Create the default "Home" page (block-composed, landing template) if it
     * does not exist, and set it as the front page when none is configured.
     * Idempotent.
     */
    public static function ensureHomePage(): Page
    {
        // type is passed explicitly: seeders run withoutModelEvents, which would
        // otherwise skip Page's creating() hook that sets the type discriminator.
        $page = Page::where('slug', 'home')->first();

        if (! $page) {
            $page = Page::create([
                'type' => 'page',
                'title' => 'Home',
                'slug' => 'home',
                'template' => 'landing',
                'status' => ContentStatus::Published,
                'published_at' => now(),
                'blocks' => [
                    ['type' => 'hero', 'data' => [
                        'heading' => 'Welcome to '.SiteSettings::siteName(),
                        'subheading' => 'A clean, modern site backed by a CMS that stays out of your way.',
                        'cta_label' => 'Get in touch',
                        'cta_url' => '/contact',
                        'image' => '',
                    ]],
                    ['type' => 'features', 'data' => [
                        'heading' => 'What we offer',
                        'items' => [
                            ['icon' => '🎯', 'title' => 'Focused', 'text' => 'Clear positioning and a plan that fits your business.'],
                            ['icon' => '🛠️', 'title' => 'Built well', 'text' => 'Fast, considered pages on a CMS you actually own.'],
                            ['icon' => '🔁', 'title' => 'Easy to update', 'text' => 'Change content yourself, whenever you need to.'],
                        ],
                    ]],
                    ['type' => 'cta', 'data' => [
                        'heading' => 'Ready to start?',
                        'text' => 'Tell us what you have in mind and we will take it from there.',
                        'button_label' => 'Say hello',
                        'button_url' => '/contact',
                    ]],
                ],
            ]);
        }

        if (! SiteSettings::frontPageId()) {
            SiteSettings::setFrontPage($page->id);
        }

        return $page;
    }
}
