<?php

namespace App\Livewire\Content;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Models\Content;
use App\Models\Page;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
class PageForm extends Component
{
    public ?int $pageId = null;

    public string $title = '';
    public string $slug = '';
    public string $body = '';
    public string $status = 'draft';
    public string $publishedAt = '';
    public string $metaTitle = '';
    public string $metaDescription = '';
    public bool $slugManuallyEdited = false;

    public function mount(?int $id = null): void
    {
        abort_unless(auth()->user()->can(Permission::ContentManage->value), 403);

        if ($id) {
            $page = Page::findOrFail($id);
            $this->pageId = $page->id;
            $this->title = $page->title;
            $this->slug = $page->slug;
            $this->body = $page->body ?? '';
            $this->status = $page->status->value;
            $this->publishedAt = $page->published_at?->format('Y-m-d\TH:i') ?? '';
            $this->metaTitle = $page->meta_title ?? '';
            $this->metaDescription = $page->meta_description ?? '';
            $this->slugManuallyEdited = true;
        }
    }

    public function updatedTitle(string $value): void
    {
        if (! $this->slugManuallyEdited) {
            $this->slug = Str::slug($value);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugManuallyEdited = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can(Permission::ContentManage->value), 403);

        $this->slug = Str::slug($this->slug) ?: Str::slug($this->title);

        $data = $this->validate([
            'title' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9][a-z0-9\-]*$/',
                function ($attribute, $value, $fail) {
                    $exists = Content::where('slug', $value)
                        ->when($this->pageId, fn ($q) => $q->where('id', '!=', $this->pageId))
                        ->exists();
                    if ($exists) {
                        $fail('This slug is already taken.');
                    }
                },
            ],
            'body' => 'nullable|string',
            'status' => 'required|in:draft,published,scheduled',
            'publishedAt' => 'nullable|date',
            'metaTitle' => 'nullable|string|max:255',
            'metaDescription' => 'nullable|string|max:500',
        ]);

        $payload = [
            'title' => $data['title'],
            'slug' => $data['slug'],
            'body' => $data['body'] ?? null,
            'status' => $data['status'],
            'published_at' => $data['publishedAt'] ? now()->parse($data['publishedAt']) : null,
            'meta_title' => $data['metaTitle'] ?: null,
            'meta_description' => $data['metaDescription'] ?: null,
            'author_id' => auth()->id(),
        ];

        if ($this->pageId) {
            Page::findOrFail($this->pageId)->update($payload);
            session()->flash('success', 'Page updated.');
        } else {
            Page::create($payload);
            session()->flash('success', 'Page created.');
        }

        $this->redirect(route('admin.pages.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.content.page-form', [
            'statuses' => ContentStatus::cases(),
            'editing' => $this->pageId !== null,
        ]);
    }

    public function title(): string
    {
        return $this->pageId ? 'Edit Page' : 'New Page';
    }
}
