<?php

namespace App\Livewire\Menus;

use App\Enums\Permission;
use App\Models\Content;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Support\ThemeManager;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class MenuBuilder extends Component
{
    public ?int $menuId = null;

    public string $name = '';

    public string $location = '';

    // New item form
    public string $newLabel = '';

    public string $newUrl = '';

    public string $newLinkType = 'url';    // 'url' | 'content'

    public ?int $newContentId = null;

    public ?int $newParentId = null;

    public function mount(?int $id = null): void
    {
        abort_unless(auth()->user()->can(Permission::ContentManage->value), 403);

        if ($id) {
            $menu = Menu::findOrFail($id);
            $this->menuId = $menu->id;
            $this->name = $menu->name;
            $this->location = $menu->location ?? '';
        }
    }

    public function saveMenu(): void
    {
        abort_unless(auth()->user()->can(Permission::ContentManage->value), 403);

        $data = $this->validate([
            'name' => 'required|string|max:255',
            'location' => [
                'nullable',
                'string',
                'max:100',
                function ($attribute, $value, $fail) {
                    if ($value === '') {
                        return;
                    }
                    $exists = Menu::where('location', $value)
                        ->when($this->menuId, fn ($q) => $q->where('id', '!=', $this->menuId))
                        ->exists();
                    if ($exists) {
                        $fail('This location is already assigned to another menu.');
                    }
                },
            ],
        ]);

        $payload = [
            'name' => $data['name'],
            'location' => $data['location'] ?: null,
        ];

        if ($this->menuId) {
            Menu::findOrFail($this->menuId)->update($payload);
            session()->flash('success', 'Menu saved.');
        } else {
            $menu = Menu::create($payload);
            $this->menuId = $menu->id;
            session()->flash('success', 'Menu created.');
            $this->redirect(route('admin.menus.edit', $this->menuId), navigate: true);
        }
    }

    public function addItem(): void
    {
        abort_unless($this->menuId, 403);
        abort_unless(auth()->user()->can(Permission::ContentManage->value), 403);

        $data = $this->validate([
            'newLabel' => 'required|string|max:255',
            'newUrl' => 'nullable|string|max:500',
            'newLinkType' => 'required|in:url,content',
            'newContentId' => 'nullable|integer|exists:contents,id',
            'newParentId' => 'nullable|integer|exists:menu_items,id',
        ]);

        $maxOrder = MenuItem::where('menu_id', $this->menuId)
            ->where('parent_id', $data['newParentId'])
            ->max('sort_order') ?? -1;

        $content = null;
        if ($data['newLinkType'] === 'content' && $data['newContentId']) {
            $base = Content::find($data['newContentId']);
            if ($base) {
                $content = $base->type === 'post'
                    ? Post::find($base->id)
                    : Page::find($base->id);
            }
        }

        MenuItem::create([
            'menu_id' => $this->menuId,
            'parent_id' => $data['newParentId'],
            'label' => $data['newLabel'],
            'url' => $data['newLinkType'] === 'url' ? ($data['newUrl'] ?: null) : null,
            'linkable_type' => $content ? get_class($content) : null,
            'linkable_id' => $content?->id,
            'sort_order' => $maxOrder + 1,
        ]);

        $this->reset('newLabel', 'newUrl', 'newContentId', 'newParentId');
        $this->newLinkType = 'url';
    }

    public function removeItem(int $itemId): void
    {
        abort_unless(auth()->user()->can(Permission::ContentManage->value), 403);
        MenuItem::where('menu_id', $this->menuId)->findOrFail($itemId)->delete();
    }

    public function moveUp(int $itemId): void
    {
        $this->reorder($itemId, -1);
    }

    public function moveDown(int $itemId): void
    {
        $this->reorder($itemId, +1);
    }

    private function reorder(int $itemId, int $direction): void
    {
        $item = MenuItem::where('menu_id', $this->menuId)->findOrFail($itemId);
        $siblings = MenuItem::where('menu_id', $this->menuId)
            ->where('parent_id', $item->parent_id)
            ->orderBy('sort_order')
            ->get();

        $index = $siblings->search(fn ($s) => $s->id === $item->id);
        $swapIndex = $index + $direction;

        if ($swapIndex < 0 || $swapIndex >= $siblings->count()) {
            return;
        }

        $swap = $siblings[$swapIndex];
        [$item->sort_order, $swap->sort_order] = [$swap->sort_order, $item->sort_order];
        $item->save();
        $swap->save();
    }

    public function render()
    {
        $menu = $this->menuId ? Menu::with(['rootItems.children'])->find($this->menuId) : null;
        $themeLocations = app(ThemeManager::class)->activeManifest()?->locations ?? [];
        $allContent = Content::published()->orderBy('title')->get();

        return view('livewire.menus.menu-builder', [
            'menu' => $menu,
            'themeLocations' => $themeLocations,
            'allContent' => $allContent,
            'editing' => $this->menuId !== null,
        ])->title($this->menuId ? 'Edit Menu' : 'New Menu');
    }
}
