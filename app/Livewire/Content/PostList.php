<?php

namespace App\Livewire\Content;

use App\Enums\Permission;
use App\Models\Post;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Posts')]
class PostList extends Component
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

        Post::findOrFail($id)->delete();
        $this->confirmingDelete = null;

        session()->flash('success', 'Post deleted.');
    }

    public function render()
    {
        return view('livewire.content.post-list', [
            'posts' => Post::with('categories')->latest()->get(),
        ]);
    }
}
