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
        $siteName = config('app.name');

        return view('theme::home', [
            'posts' => $posts,
            'themeSettings' => $this->themeSettings->active(),
            'seo' => [
                'title' => $siteName,
                'description' => null,
                'canonical' => url('/'),
                'og_type' => 'website',
            ],
        ]);
    }

    /** Blog index: all published posts, paginated. */
    public function blog(): View
    {
        $posts = Post::published()->with('categories')->latest('published_at')->paginate(10);

        return view('theme::blog', [
            'posts' => $posts,
            'themeSettings' => $this->themeSettings->active(),
            'seo' => [
                'title' => 'Blog - ' . config('app.name'),
                'description' => null,
                'canonical' => url('/blog'),
                'og_type' => 'website',
            ],
        ]);
    }

    /** Single blog post by slug. */
    public function post(string $slug): View
    {
        $post = Post::published()->with(['categories', 'tags', 'featuredImage'])->where('slug', $slug)->firstOrFail();

        return view('theme::post', [
            'content' => $post,
            'renderedBlocks' => $this->blocks->render($post->blocks),
            'themeSettings' => $this->themeSettings->active(),
            'seo' => [
                'title' => ($post->meta_title ?: $post->title) . ' - ' . config('app.name'),
                'description' => $post->meta_description ?: $post->excerpt,
                'canonical' => url('/blog/' . $post->slug),
                'og_type' => 'article',
                'og_image' => $post->featuredImage?->url(),
            ],
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
            'seo' => [
                'title' => $category->name . ' - ' . config('app.name'),
                'description' => $category->description,
                'canonical' => url('/category/' . $category->slug),
                'og_type' => 'website',
            ],
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
            'seo' => [
                'title' => '#' . $tag->name . ' - ' . config('app.name'),
                'description' => null,
                'canonical' => url('/tag/' . $tag->slug),
                'og_type' => 'website',
            ],
        ]);
    }

    /** Resolve a published page by slug (catch-all for single-segment page URLs). */
    public function show(string $slug): View
    {
        $content = Page::published()->with('featuredImage')->where('slug', $slug)->firstOrFail();

        return view('theme::page', [
            'content' => $content,
            'renderedBlocks' => $this->blocks->render($content->blocks),
            'themeSettings' => $this->themeSettings->active(),
            'seo' => [
                'title' => ($content->meta_title ?: $content->title) . ' - ' . config('app.name'),
                'description' => $content->meta_description,
                'canonical' => url('/' . $content->slug),
                'og_type' => 'website',
                'og_image' => $content->featuredImage?->url(),
            ],
        ]);
    }
}
