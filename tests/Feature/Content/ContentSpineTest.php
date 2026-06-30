<?php

use App\Enums\ContentStatus;
use App\Models\Content;
use App\Models\Page;
use App\Models\Post;
use App\Models\Theme;
use App\Support\BlockRenderer;
use App\Support\ThemeSettings;

use function Pest\Laravel\get;

// -- STI-lite: type scoping --

it('scopes Page queries to page type and Post queries to post type', function () {
    Page::create(['title' => 'About', 'slug' => 'about', 'status' => ContentStatus::Draft]);
    Post::create(['title' => 'Hello', 'slug' => 'hello', 'status' => ContentStatus::Draft]);

    expect(Page::count())->toBe(1)
        ->and(Page::first()->title)->toBe('About')
        ->and(Post::count())->toBe(1)
        ->and(Post::first()->title)->toBe('Hello')
        ->and(Content::count())->toBe(2);
});

it('sets the type discriminator automatically on create', function () {
    $page = Page::create(['title' => 'P', 'slug' => 'p', 'status' => ContentStatus::Draft]);
    $post = Post::create(['title' => 'B', 'slug' => 'b', 'status' => ContentStatus::Draft]);

    expect($page->type)->toBe('page')
        ->and($post->type)->toBe('post');
});

// -- Publish state --

it('published scope returns only published content whose time has arrived', function () {
    Page::create(['title' => 'Draft', 'slug' => 'draft', 'status' => ContentStatus::Draft]);
    Page::create(['title' => 'Future', 'slug' => 'future', 'status' => ContentStatus::Published, 'published_at' => now()->addDay()]);
    Page::create(['title' => 'Live', 'slug' => 'live', 'status' => ContentStatus::Published, 'published_at' => now()->subHour()]);

    $published = Page::published()->get();

    expect($published)->toHaveCount(1)
        ->and($published->first()->slug)->toBe('live');
});

it('isPublished reflects status and publish time', function () {
    $live = Page::make(['status' => ContentStatus::Published, 'published_at' => now()->subHour()]);
    $future = Page::make(['status' => ContentStatus::Published, 'published_at' => now()->addHour()]);
    $draft = Page::make(['status' => ContentStatus::Draft, 'published_at' => now()->subHour()]);

    expect($live->isPublished())->toBeTrue()
        ->and($future->isPublished())->toBeFalse()
        ->and($draft->isPublished())->toBeFalse();
});

// -- Theme settings resolver --

it('merges theme.json defaults with stored overrides', function () {
    $theme = Theme::create([
        'slug' => 'default',
        'name' => 'Default',
        'status' => 'active',
        'settings' => ['primary_color' => '#000000'],
        'installed_at' => now(),
    ]);

    $settings = app(ThemeSettings::class)->for($theme);

    expect($settings['primary_color'])->toBe('#000000') // override wins
        ->and($settings['show_hero'])->toBeTrue();        // default preserved
});

it('active() returns empty settings when no theme is active', function () {
    expect(app(ThemeSettings::class)->active())->toBe([]);
});

// -- Block renderer --

it('renders known block types through their theme partial', function () {
    $html = app(BlockRenderer::class)->render([
        ['type' => 'heading', 'data' => ['level' => 2, 'text' => 'Section']],
        ['type' => 'paragraph', 'data' => ['text' => 'Body text']],
    ]);

    expect($html)->toContain('<h2>Section</h2>')
        ->and($html)->toContain('<p>Body text</p>');
});

it('falls back gracefully for unknown block types', function () {
    $html = app(BlockRenderer::class)->render([
        ['type' => 'does-not-exist', 'data' => []],
    ]);

    expect($html)->toContain('unrenderable block type "does-not-exist"');
});

it('renders nothing for empty blocks', function () {
    expect(app(BlockRenderer::class)->render(null))->toBe('')
        ->and(app(BlockRenderer::class)->render([]))->toBe('');
});

// -- Front-end pipeline --

it('renders a published page through the active theme', function () {
    Page::create([
        'title' => 'About Us',
        'slug' => 'about',
        'status' => ContentStatus::Published,
        'published_at' => now()->subHour(),
        'blocks' => [['type' => 'paragraph', 'data' => ['text' => 'We are Seconds.']]],
    ]);

    get('/about')
        ->assertOk()
        ->assertSee('About Us')
        ->assertSee('We are Seconds.');
});

it('404s a draft page', function () {
    Page::create(['title' => 'Secret', 'slug' => 'secret', 'status' => ContentStatus::Draft]);

    get('/secret')->assertNotFound();
});

it('404s an unknown slug', function () {
    get('/nope-not-here')->assertNotFound();
});

it('renders the home page', function () {
    Post::create([
        'title' => 'First Post',
        'slug' => 'first-post',
        'status' => ContentStatus::Published,
        'published_at' => now()->subHour(),
    ]);

    get('/')
        ->assertOk()
        ->assertSee('First Post');
});
