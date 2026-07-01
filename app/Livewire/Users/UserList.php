<?php

namespace App\Livewire\Users;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Users')]
class UserList extends Component
{
    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can(Permission::UsersManage->value), 403);
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDelete = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = null;
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can(Permission::UsersManage->value), 403);

        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot delete your own account.');
            $this->confirmingDelete = null;

            return;
        }

        if ($this->isLastSuperAdmin($user)) {
            session()->flash('error', 'You cannot delete the last super admin.');
            $this->confirmingDelete = null;

            return;
        }

        $user->delete();
        $this->confirmingDelete = null;
        session()->flash('success', 'User deleted.');
    }

    /** True when this user is a super-admin and no other super-admin exists. */
    private function isLastSuperAdmin(User $user): bool
    {
        if (! $user->hasRole(Role::SuperAdmin->value)) {
            return false;
        }

        return User::role(Role::SuperAdmin->value)->count() <= 1;
    }

    public function render()
    {
        return view('livewire.users.user-list', [
            'users' => User::with('roles')->orderBy('name')->get(),
        ]);
    }
}
