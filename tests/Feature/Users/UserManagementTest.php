<?php

use App\Enums\Role;
use App\Livewire\Users\UserForm;
use App\Livewire\Users\UserList;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function adminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    return $user;
}

// --- Access control ---

it('blocks an editor from the user list', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    actingAs($editor)->get('/admin/users')->assertForbidden();
});

it('blocks a developer from the user list (users.manage is admin/super-admin only)', function () {
    $dev = User::factory()->create();
    $dev->assignRole(Role::Developer->value);

    actingAs($dev)->get('/admin/users')->assertForbidden();
});

it('lets an admin open the user list', function () {
    actingAs(adminUser())->get('/admin/users')->assertOk();
});

// --- Create / edit ---

it('creates a user with a role', function () {
    Livewire::actingAs(adminUser())
        ->test(UserForm::class)
        ->set('name', 'New Editor')
        ->set('email', 'editor@example.com')
        ->set('password', 'secret123')
        ->set('passwordConfirmation', 'secret123')
        ->set('role', Role::Editor->value)
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'editor@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole(Role::Editor->value))->toBeTrue()
        ->and(Hash::check('secret123', $user->password))->toBeTrue();
});

it('requires a password when creating', function () {
    Livewire::actingAs(adminUser())
        ->test(UserForm::class)
        ->set('name', 'No Password')
        ->set('email', 'nopass@example.com')
        ->set('role', Role::Editor->value)
        ->call('save')
        ->assertHasErrors(['password']);
});

it('rejects a mismatched password confirmation', function () {
    Livewire::actingAs(adminUser())
        ->test(UserForm::class)
        ->set('name', 'Mismatch')
        ->set('email', 'mismatch@example.com')
        ->set('password', 'secret123')
        ->set('passwordConfirmation', 'different')
        ->set('role', Role::Editor->value)
        ->call('save')
        ->assertHasErrors(['password']);
});

it('edits a user and changes their role without touching the password when left blank', function () {
    $target = User::factory()->create(['password' => Hash::make('original')]);
    $target->assignRole(Role::Editor->value);

    Livewire::actingAs(adminUser())
        ->test(UserForm::class, ['id' => $target->id])
        ->set('role', Role::Admin->value)
        ->call('save')
        ->assertHasNoErrors();

    $target->refresh();
    expect($target->hasRole(Role::Admin->value))->toBeTrue()
        ->and($target->hasRole(Role::Editor->value))->toBeFalse()
        ->and(Hash::check('original', $target->password))->toBeTrue();
});

it('enforces unique email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::actingAs(adminUser())
        ->test(UserForm::class)
        ->set('name', 'Dup')
        ->set('email', 'taken@example.com')
        ->set('password', 'secret123')
        ->set('passwordConfirmation', 'secret123')
        ->set('role', Role::Editor->value)
        ->call('save')
        ->assertHasErrors(['email']);
});

// --- Guards ---

it('cannot delete your own account', function () {
    $admin = adminUser();

    Livewire::actingAs($admin)
        ->test(UserList::class)
        ->call('delete', $admin->id);

    expect(User::find($admin->id))->not->toBeNull();
});

it('cannot delete the last super admin', function () {
    $super = User::factory()->create();
    $super->assignRole(Role::SuperAdmin->value);

    Livewire::actingAs(adminUser())
        ->test(UserList::class)
        ->call('delete', $super->id);

    expect(User::find($super->id))->not->toBeNull();
});

it('can delete a normal user', function () {
    $target = User::factory()->create();
    $target->assignRole(Role::Editor->value);

    Livewire::actingAs(adminUser())
        ->test(UserList::class)
        ->call('delete', $target->id);

    expect(User::find($target->id))->toBeNull();
});

it('cannot demote the last super admin', function () {
    $super = User::factory()->create();
    $super->assignRole(Role::SuperAdmin->value);

    Livewire::actingAs(adminUser())
        ->test(UserForm::class, ['id' => $super->id])
        ->set('role', Role::Admin->value)
        ->call('save')
        ->assertHasErrors(['role']);

    expect($super->fresh()->hasRole(Role::SuperAdmin->value))->toBeTrue();
});

it('can demote a super admin when another exists', function () {
    $super1 = User::factory()->create();
    $super1->assignRole(Role::SuperAdmin->value);
    $super2 = User::factory()->create();
    $super2->assignRole(Role::SuperAdmin->value);

    Livewire::actingAs(adminUser())
        ->test(UserForm::class, ['id' => $super1->id])
        ->set('role', Role::Admin->value)
        ->call('save')
        ->assertHasNoErrors();

    expect($super1->fresh()->hasRole(Role::Admin->value))->toBeTrue();
});
