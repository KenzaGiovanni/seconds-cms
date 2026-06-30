<?php

namespace App\Livewire\Content;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Models\Category;
use App\Models\Content;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
class PostForm extends Component
{
    public ?int $postId = null;

    public string $title = '';
    public string $slug = '';
    public string $body = '';
    public string $excerpt = '';
    public string $status = 'draft';
    public string $publishedAt = '';
    public string $metaTitle = '';
    public string $metaDescription = '';
    public bool $slugManuallyEdited = false;

    /** @var list<int> */
    public array $selectedCategories = [];

    /** Comma-separated tag names as typed by the user. */
    public string $tagInput = '';

    public ?int $featuredImageId = null;
    public ?string $featuredImageUrl = null;

    /** @var list<array{type: string, data: array<string, mixed>}> */
    public array $blocks = [];

    public string $newBlockType = 'paragraph';

    public function mount(?int $id = null): void
    {
        abort_unless(auth()->user()->can(Permission::ContentManage->value), 403);

        if ($id) {
            $post = Post::with(['categories', 'tags'])->findOrFail($id);
            $this->postId = $post->id;
            $this->title = $post->title;
            $this->slug = $post->slug;
            $this->body = $post->body ?? '';
            $this->excerpt = $post->excerpt ?? '';
            $this->status = $post->status->value;
            $this->publishedAt = $post->published_at?->format('Y-m-d\TH:i') ?? '';
            $this->metaTitle = $post->meta_title ?? '';
            $this->metaDescription = $post->meta_description ?? '';
            $this->selectedCategories = $post->categories->pluck('id')->map(fn ($id) => (int) $id)->all();
            $this->tagInput = $post->tags->pluck('name')->join(', ');
            $this->slugManuallyEdited = true;
            $this->featuredImageId = $post->featured_image_id;
            $this->featuredImageUrl = $post->featuredImage?->url();
            $this->blocks = $post->blocks ?? [];
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

    #[On('media-selected')]
    public function onMediaSelected(int $id, string $url): void
    {
        $this->featuredImageId = $id;
        $this->featuredImageUrl = $url;
    }

    public function removeFeaturedImage(): void
    {
        $this->featuredImageId = null;
        $this->featuredImageUrl = null;
    }

    public function addBlock(): void
    {
        $this->blocks[] = $this->defaultBlock($this->newBlockType);
    }

    public function removeBlock(int $index): void
    {
        array_splice($this->blocks, $index, 1);
        $this->blocks = array_values($this->blocks);
    }

    public function moveBlockUp(int $index): void
    {
        if ($index === 0) {
            return;
        }
        [$this->blocks[$index - 1], $this->blocks[$index]] = [$this->blocks[$index], $this->blocks[$index - 1]];
    }

    public function moveBlockDown(int $index): void
    {
        if ($index >= count($this->blocks) - 1) {
            return;
        }
        [$this->blocks[$index], $this->blocks[$index + 1]] = [$this->blocks[$index + 1], $this->blocks[$index]];
    }

    private function defaultBlock(string $type): array
    {
        return match ($type) {
            'heading' => ['type' => 'heading', 'data' => ['level' => 2, 'text' => '']],
            default => ['type' => 'paragraph', 'data' => ['text' => '']],
        };
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
                        ->when($this->postId, fn ($q) => $q->where('id', '!=', $this->postId))
                        ->exists();
                    if ($exists) {
                        $fail('This slug is already taken.');
                    }
                },
            ],
            'body' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
            'status' => 'required|in:draft,published,scheduled',
            'publishedAt' => 'nullable|date',
            'metaTitle' => 'nullable|string|max:255',
            'metaDescription' => 'nullable|string|max:500',
            'selectedCategories' => 'nullable|array',
            'selectedCategories.*' => 'integer|exists:categories,id',
            'blocks' => 'nullable|array',
        ]);

        $payload = [
            'title' => $data['title'],
            'slug' => $data['slug'],
            'body' => $data['body'] ?? null,
            'excerpt' => $data['excerpt'] ?? null,
            'status' => $data['status'],
            'published_at' => $data['publishedAt'] ? now()->parse($data['publishedAt']) : null,
            'meta_title' => $data['metaTitle'] ?: null,
            'meta_description' => $data['metaDescription'] ?: null,
            'blocks' => count($this->blocks) ? $this->blocks : null,
            'featured_image_id' => $this->featuredImageId,
            'author_id' => auth()->id(),
        ];

        if ($this->postId) {
            $post = Post::findOrFail($this->postId);
            $post->update($payload);
        } else {
            $post = Post::create($payload);
        }

        $post->categories()->sync($data['selectedCategories'] ?? []);
        $post->tags()->sync($this->resolveTagIds($this->tagInput));

        session()->flash('success', $this->postId ? 'Post updated.' : 'Post created.');
        $this->redirect(route('admin.posts.index'), navigate: true);
    }

    /** Finds or creates tags by name and returns their IDs. */
    private function resolveTagIds(string $tagInput): array
    {
        if (trim($tagInput) === '') {
            return [];
        }

        return collect(explode(',', $tagInput))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->map(function ($name) {
                return Tag::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name]
                )->id;
            })
            ->all();
    }

    public function render()
    {
        return view('livewire.content.post-form', [
            'statuses' => ContentStatus::cases(),
            'categories' => Category::orderBy('name')->get(),
            'editing' => $this->postId !== null,
        ]);
    }
}
