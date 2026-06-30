<?php

use App\Enums\ContentStatus;
use App\Models\Page;
use App\Models\Post;

use function Pest\Laravel\get;

// -- Meta tags on content pages --

it('renders meta title from content meta_title field', function () {
    Page::create([
        'title' => 'About Us',
        'slug' => 'about',
        'status' => ContentStatus::Published,
        'published_at' => now()->subHour(),
        'meta_title' => 'About Our Company',
    ]);

    get('/about')->assertSee('<title>About Our Company', false);
});

it('falls back to content title when no meta_title set', function () {
    Page::create([
        'title' => 'Services',
        'slug' => 'services',
        'status' => ContentStatus::Published,
        'published_at' => now()->subHour(),
    ]);

    get('/services')->assertSee('<title>Services', false);
});

it('renders meta description', function () {
    Page::create([
        'title' => 'Contact',
        'slug' => 'contact',
        'status' => ContentStatus::Published,
        'published_at' => now()->subHour(),
        'meta_description' => 'Get in touch with us today.',
    ]);

    get('/contact')->assertSee('content="Get in touch with us today."', false);
});

it('renders OG tags on a published page', function () {
    Page::create([
        'title' => 'Team',
        'slug' => 'team',
        'status' => ContentStatus::Published,
        'published_at' => now()->subHour(),
        'meta_title' => 'Meet the Team',
    ]);

    get('/team')
        ->assertSee('og:title', false)
        ->assertSee('Meet the Team', false)
        ->assertSee('og:type', false);
});

it('renders canonical URL', function () {
    Page::create([
        'title' => 'Privacy',
        'slug' => 'privacy',
        'status' => ContentStatus::Published,
        'published_at' => now()->subHour(),
    ]);

    get('/privacy')->assertSee('rel="canonical"', false);
});

it('renders OG type article on blog posts', function () {
    Post::create([
        'title' => 'Hello World',
        'slug' => 'hello-world',
        'status' => ContentStatus::Published,
        'published_at' => now()->subHour(),
    ]);

    get('/blog/hello-world')->assertSee('og:type', false)->assertSee('article', false);
});

// -- Sitemap --

it('sitemap.xml lists published pages and posts only', function () {
    Page::create(['title' => 'About', 'slug' => 'about', 'status' => ContentStatus::Published, 'published_at' => now()->subHour()]);
    Page::create(['title' => 'Draft', 'slug' => 'draft-page', 'status' => ContentStatus::Draft]);
    Post::create(['title' => 'Post 1', 'slug' => 'post-1', 'status' => ContentStatus::Published, 'published_at' => now()->subHour()]);
    Post::create(['title' => 'Secret', 'slug' => 'secret-post', 'status' => ContentStatus::Draft]);

    $response = get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml');

    $response->assertSee('/about', false)
        ->assertSee('/blog/post-1', false)
        ->assertDontSee('/draft-page', false)
        ->assertDontSee('/secret-post', false);
});

it('sitemap.xml returns valid XML', function () {
    get('/sitemap.xml')
        ->assertOk()
        ->assertSee('<?xml', false)
        ->assertSee('urlset', false);
});

// -- Robots.txt --

it('robots.txt returns allow-all with sitemap pointer', function () {
    get('/robots.txt')
        ->assertOk()
        ->assertSee('User-agent: *', false)
        ->assertSee('Allow: /', false)
        ->assertSee('Sitemap:', false);
});
