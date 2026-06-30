<?php

use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('redirects guests from the admin area to login', function () {
    get('/admin')->assertRedirect(route('login'));
});

it('renders the login screen for guests', function () {
    get('/admin/login')
        ->assertOk()
        ->assertSeeLivewire(Login::class);
});

it('authenticates with valid credentials and lands on the dashboard', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.dashboard'));

    expect(auth()->check())->toBeTrue();
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('authenticate')
        ->assertHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('lets an authenticated user view the dashboard', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSee('Dashboard');
});

it('logs the user out', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    post('/logout')->assertRedirect(route('login'));

    expect(auth()->check())->toBeFalse();
});

it('redirects authenticated users away from the login screen', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/login')
        ->assertRedirect(route('admin.dashboard'));
});
