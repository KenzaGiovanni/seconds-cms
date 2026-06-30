<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Post;
use App\Support\BlockRenderer;
use App\Support\ThemeSettings;
use Illuminate\Contracts\View\View;

class FrontController extends Controller
{
    public function __construct(
        private BlockRenderer $blocks,
        private ThemeSettings $themeSettings,
    ) {}

    /** Home page: render the active theme's home template with recent posts. */
    public function home(): View
    {
        $posts = Post::published()->latest('published_at')->take(5)->get();

        return view('theme::home', [
            'posts' => $posts,
            'themeSettings' => $this->themeSettings->active(),
        ]);
    }

    /** Resolve a published content item by slug and render its type template. */
    public function show(string $slug): View
    {
        $content = Content::published()->where('slug', $slug)->firstOrFail();

        return view("theme::{$content->type}", [
            'content' => $content,
            'renderedBlocks' => $this->blocks->render($content->blocks),
            'themeSettings' => $this->themeSettings->active(),
        ]);
    }
}
