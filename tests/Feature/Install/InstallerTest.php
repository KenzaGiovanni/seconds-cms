<?php

use App\Enums\Role;
use App\Livewire\Install\Installer;
use App\Models\Setting;
use App\Models\Theme;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

use function Pest\Laravel\get;
use function Pest\Laravel\seed;

it('shows the install page when no users exist', function () {
    get('/install')->assertOk();
});

it('redirects away from install if users already exist', function () {
    seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    $user->assignRole(Role::SuperAdmin->value);

    get('/install')->assertRedirect(route('login'));
});

it('runs the install flow and creates the super-admin', function () {
    Artisan::shouldReceive('call')->with('migrate', ['--force' => true])->once();

    Livewire::test(Installer::class)
        ->set('siteName', 'Test Store')
        ->set('email', 'owner@example.com')
        ->set('password', 'secret123')
        ->set('passwordConfirmation', 'secret123')
        ->call('install')
        ->assertRedirect(route('admin.dashboard'));

    expect(User::where('email', 'owner@example.com')->exists())->toBeTrue();

    $user = User::where('email', 'owner@example.com')->first();
    expect($user->hasRole(Role::SuperAdmin->value))->toBeTrue();
});

it('activates the default theme during install', function () {
    Artisan::shouldReceive('call')->with('migrate', ['--force' => true])->once();

    Livewire::test(Installer::class)
        ->set('siteName', 'Test Store')
        ->set('email', 'owner@example.com')
        ->set('password', 'secret123')
        ->set('passwordConfirmation', 'secret123')
        ->call('install');

    expect(Theme::where('slug', 'default')->where('status', 'active')->exists())->toBeTrue();
});

it('saves the site name during install', function () {
    Artisan::shouldReceive('call')->with('migrate', ['--force' => true])->once();

    Livewire::test(Installer::class)
        ->set('siteName', 'My New Store')
        ->set('email', 'owner@example.com')
        ->set('password', 'secret123')
        ->set('passwordConfirmation', 'secret123')
        ->call('install');

    expect(Setting::get('site_name'))->toBe('My New Store');
});

it('validates required fields', function () {
    Livewire::test(Installer::class)
        ->call('install')
        ->assertHasErrors(['siteName', 'email', 'password']);
});

it('validates password confirmation', function () {
    Livewire::test(Installer::class)
        ->set('siteName', 'Store')
        ->set('email', 'owner@example.com')
        ->set('password', 'secret123')
        ->set('passwordConfirmation', 'different')
        ->call('install')
        ->assertHasErrors(['password']);
});
