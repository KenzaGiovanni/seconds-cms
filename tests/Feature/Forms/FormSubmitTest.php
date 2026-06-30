<?php

use App\Enums\ContentStatus;
use App\Models\Form;
use App\Models\Page;
use App\Models\Theme;
use App\Support\FormRenderer;
use Database\Seeders\RolesAndPermissionsSeeder;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);

    Theme::updateOrCreate(
        ['slug' => 'default'],
        ['name' => 'Seconds Default', 'status' => 'active', 'settings' => [], 'installed_at' => now()],
    );

    $this->form = Form::create([
        'name' => 'Contact',
        'slug' => 'contact',
        'fields' => [
            ['key' => 'name', 'type' => 'text', 'label' => 'Your name', 'required' => true],
            ['key' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true],
            ['key' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => false],
        ],
        'recipient_email' => 'owner@example.com',
        'success_message' => 'Thanks - we will be in touch.',
    ]);
});

// -- Rendering --

it('renders a form on a page through the form block', function () {
    Page::create([
        'title' => 'Contact Us',
        'slug' => 'contact-us',
        'status' => ContentStatus::Published,
        'published_at' => now()->subHour(),
        'blocks' => [
            ['type' => 'form', 'data' => ['slug' => 'contact']],
        ],
    ]);

    get('/contact-us')
        ->assertOk()
        ->assertSee('seconds-form')
        ->assertSee('Your name')
        ->assertSee('Email')
        ->assertSee(route('forms.submit', 'contact'));
});

it('returns empty markup for an unknown form slug', function () {
    expect(FormRenderer::render('nope'))->toBe('')
        ->and(FormRenderer::render(null))->toBe('');
});

// -- Capture --

it('captures a valid submission', function () {
    $this->from('/contact-us')
        ->post('/forms/contact', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'message' => 'Hello there.',
        ])
        ->assertRedirect('/contact-us')
        ->assertSessionHas('form_success', 'contact');

    $this->assertDatabaseHas('form_submissions', ['form_id' => $this->form->id]);

    $submission = $this->form->submissions()->first();
    expect($submission->data)->toMatchArray([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'message' => 'Hello there.',
    ]);
});

it('rejects a submission missing a required field', function () {
    $this->from('/contact-us')
        ->post('/forms/contact', ['email' => 'ada@example.com'])
        ->assertRedirect('/contact-us')
        ->assertSessionHasErrors(['name'], errorBag: 'contact');

    expect($this->form->submissions()->count())->toBe(0);
});

it('validates email fields', function () {
    $this->from('/contact-us')
        ->post('/forms/contact', ['name' => 'Ada', 'email' => 'not-an-email'])
        ->assertSessionHasErrors(['email'], errorBag: 'contact');

    expect($this->form->submissions()->count())->toBe(0);
});

it('drops honeypot (bot) submissions silently', function () {
    $this->from('/contact-us')
        ->post('/forms/contact', [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            '_hpot' => 'gotcha',
        ])
        ->assertRedirect('/contact-us')
        ->assertSessionHas('form_success', 'contact');

    expect($this->form->submissions()->count())->toBe(0);
});

it('404s on an unknown form slug', function () {
    post('/forms/does-not-exist', ['x' => 'y'])->assertNotFound();
});
