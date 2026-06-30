<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Content;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use App\Support\BlockRenderer;
use App\Support\ThemeSettings;
use Illuminate\Contracts\View\View;

class FrontController extends Controller
{
    public function __construct(
        private BlockRenderer $blocks,
        private ThemeSettings $themeSettings,
    ) {}

    /** Home page: renders the active theme's home template with recent posts. */
    public function home(): View
    {
        $posts = Post::published()->with('categories')->latest('published_at')->take(5)->get();

        return view('theme::home', [
            'posts' => $posts,
            'themeSettings' => $this->themeSettings->active(),
        ]);
    }

    /** Blog index: all published posts, paginated. */
    public function blog(): View
    {
        $posts = Post::published()->with('categories')->latest('published_at')->paginate(10);

        return view('theme::blog', [
            'posts' => $posts,
            'themeSettings' => $this->themeSettings->active(),
        ]);
    }

    /** Single blog post by slug. */
    public function post(string $slug): View
    {
        $post = Post::published()->with(['categories', 'tags'])->where('slug', $slug)->firstOrFail();

        return view('theme::post', [
            'content' => $post,
            'renderedBlocks' => $this->blocks->render($post->blocks),
            'themeSettings' => $this->themeSettings->active(),
        ]);
    }

    /** Category archive: published posts in this category. */
    public function category(string $slug): View
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $posts = Post::published()
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $category->id))
            ->with('categories')
            ->latest('published_at')
            ->paginate(10);

        return view('theme::category', [
            'category' => $category,
            'posts' => $posts,
            'themeSettings' => $this->themeSettings->active(),
        ]);
    }

    /** Tag archive: published posts with this tag. */
    public function tag(string $slug): View
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();
        $posts = Post::published()
            ->whereHas('tags', fn ($q) => $q->where('tags.id', $tag->id))
            ->with('categories')
            ->latest('published_at')
            ->paginate(10);

        return view('theme::tag', [
            'tag' => $tag,
            'posts' => $posts,
            'themeSettings' => $this->themeSettings->active(),
        ]);
    }

    /** Resolve a published page by slug (catch-all for single-segment page URLs). */
    public function show(string $slug): View
    {
        $content = Page::published()->where('slug', $slug)->firstOrFail();

        return view('theme::page', [
            'content' => $content,
            'renderedBlocks' => $this->blocks->render($content->blocks),
            'themeSettings' => $this->themeSettings->active(),
        ]);
    }
}
