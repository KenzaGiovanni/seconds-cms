<?php

use App\Enums\ContentStatus;
use App\Models\Page;
use App\Models\Theme;
use Database\Seeders\DemoContentSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

use function Pest\Laravel\get;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);

    Theme::updateOrCreate(
        ['slug' => 'default'],
        ['name' => 'Seconds Default', 'status' => 'active', 'settings' => [], 'installed_at' => now()],
    );
});

it('renders a hero block with a button', function () {
    Page::create([
        'title' => 'Landing',
        'slug' => 'landing',
        'status' => ContentStatus::Published,
        'published_at' => now()->subHour(),
        'blocks' => [
            ['type' => 'hero', 'data' => [
                'heading' => 'Welcome aboard',
                'subheading' => 'The clean way to ship.',
                'cta_label' => 'Start now',
                'cta_url' => '/start',
            ]],
        ],
    ]);

    get('/landing')
        ->assertOk()
        ->assertSee('block-hero')
        ->assertSee('Welcome aboard')
        ->assertSee('The clean way to ship.')
        ->assertSee('Start now')
        ->assertSee('/start');
});

it('renders a feature grid with icons and cards', function () {
    Page::create([
        'title' => 'Services',
        'slug' => 'services',
        'status' => ContentStatus::Published,
        'published_at' => now()->subHour(),
        'blocks' => [
            ['type' => 'features', 'data' => [
                'heading' => 'Our services',
                'items' => [
                    ['icon' => '⚡', 'title' => 'Speed', 'text' => 'Fast delivery.'],
                    ['icon' => '🎨', 'title' => 'Design', 'text' => 'Clean and modern.'],
                ],
            ]],
        ],
    ]);

    get('/services')
        ->assertOk()
        ->assertSee('Our services')
        ->assertSee('Speed')
        ->assertSee('Fast delivery.')
        ->assertSee('Design')
        ->assertSee('⚡', false);
});

it('renders a gallery block', function () {
    Page::create([
        'title' => 'Gallery',
        'slug' => 'gallery',
        'status' => ContentStatus::Published,
        'published_at' => now()->subHour(),
        'blocks' => [
            ['type' => 'gallery', 'data' => [
                'items' => [
                    ['url' => 'http://example.com/a.jpg', 'caption' => 'First shot'],
                    ['url' => 'http://example.com/b.jpg', 'caption' => 'Second shot'],
                ],
            ]],
        ],
    ]);

    get('/gallery')
        ->assertOk()
        ->assertSee('http://example.com/a.jpg')
        ->assertSee('First shot')
        ->assertSee('Second shot');
});

it('renders a call-to-action block', function () {
    Page::create([
        'title' => 'CTA',
        'slug' => 'cta',
        'status' => ContentStatus::Published,
        'published_at' => now()->subHour(),
        'blocks' => [
            ['type' => 'cta', 'data' => [
                'heading' => 'Ready?',
                'text' => 'Let us talk.',
                'button_label' => 'Contact',
                'button_url' => '/contact',
            ]],
        ],
    ]);

    get('/cta')
        ->assertOk()
        ->assertSee('block-cta')
        ->assertSee('Ready?')
        ->assertSee('Let us talk.')
        ->assertSee('Contact');
});

it('seeds a renderable sample page with stacked blocks and a form', function () {
    seed(DemoContentSeeder::class);

    get('/sample')
        ->assertOk()
        ->assertSee('Build it once. Update it in seconds.')
        ->assertSee('What we do')
        ->assertSee('Strategy')
        ->assertSee('Ready to start?')
        ->assertSee('seconds-form')
        ->assertSee('Your name');
});
