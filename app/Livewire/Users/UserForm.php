<?php

namespace App\Livewire\Users;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class UserForm extends Component
{
    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public string $role = '';

    public function mount(?int $id = null): void
    {
        abort_unless(auth()->user()->can(Permission::UsersManage->value), 403);

        if ($id) {
            $user = User::findOrFail($id);
            $this->userId = $user->id;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->role = $user->roles->first()?->name ?? '';
        } else {
            $this->role = Role::Editor->value;
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can(Permission::UsersManage->value), 403);

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email'.($this->userId ? ','.$this->userId : ''),
            'role' => 'required|in:'.implode(',', Role::values()),
            'password' => ($this->userId ? 'nullable' : 'required').'|string|min:8|same:passwordConfirmation',
        ];

        $data = $this->validate($rules);

        // Guard: don't let the last super-admin be demoted to a lesser role.
        if ($this->userId) {
            $user = User::findOrFail($this->userId);
            if ($user->hasRole(Role::SuperAdmin->value)
                && $this->role !== Role::SuperAdmin->value
                && User::role(Role::SuperAdmin->value)->count() <= 1) {
                $this->addError('role', 'You cannot demote the last super admin.');

                return;
            }
        } else {
            $user = new User;
        }

        $user->name = $data['name'];
        $user->email = $data['email'];

        if (! empty($data['password'])) {
            $user->password = $data['password']; // 'hashed' cast hashes on save
        }

        if (! $this->userId) {
            $user->email_verified_at = now(); // admin-provisioned, no verification step
        }

        $user->save();
        $user->syncRoles([$this->role]);

        session()->flash('success', $this->userId ? 'User updated.' : 'User created.');
        $this->redirect(route('admin.users.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.users.user-form', [
            'roles' => Role::cases(),
            'editing' => $this->userId !== null,
        ]);
    }

    public function title(): string
    {
        return $this->userId ? 'Edit User' : 'New User';
    }
}
