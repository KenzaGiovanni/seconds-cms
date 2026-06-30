<?php

namespace App\Livewire\Menus;

use App\Enums\Permission;
use App\Models\Menu;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Menus')]
class MenuList extends Component
{
    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can(Permission::ContentManage->value), 403);
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
        abort_unless(auth()->user()->can(Permission::ContentManage->value), 403);
        Menu::findOrFail($id)->delete();
        $this->confirmingDelete = null;
        session()->flash('success', 'Menu deleted.');
    }

    public function render()
    {
        return view('livewire.menus.menu-list', [
            'menus' => Menu::withCount('items')->latest()->get(),
        ]);
    }
}
