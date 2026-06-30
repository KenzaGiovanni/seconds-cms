<?php

use App\Enums\ContentStatus;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Theme;
use Database\Seeders\RolesAndPermissionsSeeder;

use function Pest\Laravel\get;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);

    // Ensure the default theme is active for all front-end tests.
    Theme::updateOrCreate(
        ['slug' => 'default'],
        [
            'name'         => 'Seconds Default',
            'status'       => 'active',
            'settings'     => [
                'primary_color'   => '#16513F',
                'show_hero'       => true,
                'hero_heading'    => 'Hello World',
                'hero_subheading' => 'Test tagline',
                'footer_text'     => 'Test footer',
            ],
            'installed_at' => now(),
        ]
    );
});

// -- Home --

it('renders the home page with the hero heading', function () {
    get('/')->assertOk()->assertSee('Hello World');
});

it('shows theme tagline in hero', function () {
    get('/')->assertOk()->assertSee('Test tagline');
});

it('shows latest posts on the home page', function () {
    Post::create([
        'title'        => 'My First Post',
        'slug'         => 'my-first-post',
        'status'       => ContentStatus::Published,
        'published_at' => now()->subHour(),
    ]);

    get('/')->assertOk()->assertSee('My First Post');
});

it('hides hero when show_hero is false', function () {
    Theme::where('slug', 'default')->update([
        'settings' => [
            'primary_color'   => '#16513F',
            'show_hero'       => false,
            'hero_heading'    => 'Hello World',
            'hero_subheading' => 'Test tagline',
            'footer_text'     => '',
        ],
    ]);

    get('/')->assertOk()->assertDontSee('Test tagline');
});

// -- Page --

it('renders a published page with blocks', function () {
    Page::create([
        'title'        => 'About Us',
        'slug'         => 'about-us',
        'status'       => ContentStatus::Published,
        'published_at' => now()->subHour(),
        'blocks'       => [
            ['type' => 'heading',   'data' => ['level' => 2, 'text' => 'Our Story']],
            ['type' => 'paragraph', 'data' => ['text' => 'We started in 2020.']],
        ],
    ]);

    get('/about-us')
        ->assertOk()
        ->assertSee('About Us')
        ->assertSee('Our Story')
        ->assertSee('We started in 2020.');
});

it('renders a divider block without erroring', function () {
    Page::create([
        'title'        => 'Divider Page',
        'slug'         => 'divider-page',
        'status'       => ContentStatus::Published,
        'published_at' => now()->subHour(),
        'blocks'       => [
            ['type' => 'paragraph', 'data' => ['text' => 'Above.']],
            ['type' => 'divider',   'data' => []],
            ['type' => 'paragraph', 'data' => ['text' => 'Below.']],
        ],
    ]);

    get('/divider-page')
        ->assertOk()
        ->assertSee('Above.')
        ->assertSee('Below.');
});

it('uses fallback block for unknown types', function () {
    Page::create([
        'title'        => 'Unknown Block',
        'slug'         => 'unknown-block',
        'status'       => ContentStatus::Published,
        'published_at' => now()->subHour(),
        'blocks'       => [
            ['type' => 'widget_xyz', 'data' => ['foo' => 'bar']],
        ],
    ]);

    get('/unknown-block')->assertOk();
});

// -- Post --

it('renders a published post with date and categories', function () {
    $cat = Category::create(['name' => 'Tech', 'slug' => 'tech']);

    $post = Post::create([
        'title'        => 'Laravel Tips',
        'slug'         => 'laravel-tips',
        'status'       => ContentStatus::Published,
        'published_at' => now()->subDay(),
        'blocks'       => [
            ['type' => 'paragraph', 'data' => ['text' => 'Use Eloquent scopes.']],
        ],
    ]);
    $post->categories()->attach($cat->id);

    get('/blog/laravel-tips')
        ->assertOk()
        ->assertSee('Laravel Tips')
        ->assertSee('Tech')
        ->assertSee('Use Eloquent scopes.');
});

it('renders post tags', function () {
    $tag = Tag::create(['name' => 'php', 'slug' => 'php']);

    $post = Post::create([
        'title'        => 'Tagged Post',
        'slug'         => 'tagged-post',
        'status'       => ContentStatus::Published,
        'published_at' => now()->subHour(),
    ]);
    $post->tags()->attach($tag->id);

    get('/blog/tagged-post')
        ->assertOk()
        ->assertSee('#php');
});

// -- Blog listing --

it('renders the blog index with posts', function () {
    Post::create([
        'title'        => 'Post Alpha',
        'slug'         => 'post-alpha',
        'status'       => ContentStatus::Published,
        'published_at' => now()->subHour(),
    ]);

    get('/blog')->assertOk()->assertSee('Post Alpha');
});

// -- Category / Tag archive --

it('renders a category archive page', function () {
    $cat = Category::create(['name' => 'Design', 'slug' => 'design', 'description' => 'Design articles']);
    $post = Post::create([
        'title'        => 'Design Post',
        'slug'         => 'design-post',
        'status'       => ContentStatus::Published,
        'published_at' => now()->subHour(),
    ]);
    $post->categories()->attach($cat->id);

    get('/category/design')
        ->assertOk()
        ->assertSee('Design')
        ->assertSee('Design Post');
});

it('renders a tag archive page', function () {
    $tag = Tag::create(['name' => 'css', 'slug' => 'css']);
    $post = Post::create([
        'title'        => 'CSS Tricks',
        'slug'         => 'css-tricks',
        'status'       => ContentStatus::Published,
        'published_at' => now()->subHour(),
    ]);
    $post->tags()->attach($tag->id);

    get('/tag/css')
        ->assertOk()
        ->assertSee('#css')
        ->assertSee('CSS Tricks');
});

// -- Theme settings resolve --

it('injects the accent color into the theme layout', function () {
    Theme::where('slug', 'default')->update([
        'settings' => array_merge(
            Theme::where('slug', 'default')->first()->settings,
            ['primary_color' => '#FF5500']
        ),
    ]);

    get('/')->assertOk()->assertSee('#FF5500');
});

it('declared settings are accessible via themeSettings in views', function () {
    get('/')->assertOk()->assertSee('Test footer');
});
