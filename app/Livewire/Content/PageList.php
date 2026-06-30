<?php

namespace App\Livewire\Content;

use App\Enums\Permission;
use App\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Pages')]
class PageList extends Component
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

        Page::findOrFail($id)->delete();
        $this->confirmingDelete = null;

        session()->flash('success', 'Page deleted.');
    }

    public function render()
    {
        return view('livewire.content.page-list', [
            'pages' => Page::latest()->get(),
        ]);
    }
}
