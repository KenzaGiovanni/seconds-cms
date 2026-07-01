<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tag;
use App\Support\BlockRenderer;
use App\Support\Feature;
use App\Support\SiteSettings;
use App\Support\ThemeSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class FrontController extends Controller
{
    public function __construct(
        private BlockRenderer $blocks,
        private ThemeSettings $themeSettings,
    ) {}

    /** Home page: a static front page (Website Settings) or the blog feed fallback. */
    public function home(): View
    {
        $settings = $this->themeSettings->active();
        $frontPage = SiteSettings::frontPage();

        if ($frontPage) {
            return view('theme::landing', [
                'content' => $frontPage,
                'renderedBlocks' => $this->blocks->render($frontPage->blocks),
                'themeSettings' => $settings,
                'seo' => [
                    'title' => $frontPage->meta_title ?: config('app.name'),
                    'description' => $frontPage->meta_description,
                    'canonical' => url('/'),
                    'og_type' => 'website',
                    'og_image' => $frontPage->featuredImage?->url(),
                ],
            ]);
        }

        $posts = Post::published()->with('categories')->latest('published_at')->take(5)->get();

        return view('theme::home', [
            'posts' => $posts,
            'themeSettings' => $settings,
            'seo' => [
                'title' => config('app.name'),
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
                'title' => 'Blog - '.config('app.name'),
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
                'title' => ($post->meta_title ?: $post->title).' - '.config('app.name'),
                'description' => $post->meta_description ?: $post->excerpt,
                'canonical' => url('/blog/'.$post->slug),
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
                'title' => $category->name.' - '.config('app.name'),
                'description' => $category->description,
                'canonical' => url('/category/'.$category->slug),
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
                'title' => '#'.$tag->name.' - '.config('app.name'),
                'description' => null,
                'canonical' => url('/tag/'.$tag->slug),
                'og_type' => 'website',
            ],
        ]);
    }

    /** Shop index: published product grid, optional category filter. */
    public function shop(Request $request): View
    {
        abort_unless(Feature::ecommerce(), 404);

        $categorySlug = $request->query('category');
        $activeCategory = $categorySlug
            ? ProductCategory::where('slug', $categorySlug)->firstOrFail()
            : null;

        $query = Product::published()->with(['categories', 'featuredImage', 'variants']);
        if ($activeCategory) {
            $query->whereHas('categories', fn ($q) => $q->where('product_categories.id', $activeCategory->id));
        }

        $products = $query->latest()->get();
        $categories = ProductCategory::whereHas('products', fn ($q) => $q->where('status', 'published'))
            ->orderBy('name')
            ->get();

        return view('theme::shop.index', [
            'products' => $products,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'themeSettings' => $this->themeSettings->active(),
            'seo' => [
                'title' => 'Shop - '.config('app.name'),
                'description' => null,
                'canonical' => url('/shop'),
                'og_type' => 'website',
            ],
        ]);
    }

    /** Product detail page. Variant selection handled via embedded Livewire component. */
    public function product(string $slug): View
    {
        abort_unless(Feature::ecommerce(), 404);

        $product = Product::published()
            ->with(['categories', 'featuredImage', 'variants'])
            ->where('slug', $slug)
            ->firstOrFail();

        return view('theme::shop.product', [
            'product' => $product,
            'renderedBlocks' => $this->blocks->render($product->blocks),
            'themeSettings' => $this->themeSettings->active(),
            'seo' => [
                'title' => $product->name.' - '.config('app.name'),
                'description' => $product->description,
                'canonical' => url('/shop/'.$product->slug),
                'og_type' => 'product',
                'og_image' => $product->featuredImage?->url(),
            ],
        ]);
    }

    /** Cart page: the interactive item list is a Livewire island in the theme view. */
    public function cart(): View
    {
        abort_unless(Feature::ecommerce(), 404);

        return view('theme::cart', [
            'themeSettings' => $this->themeSettings->active(),
            'seo' => [
                'title' => 'Your Cart - '.config('app.name'),
                'description' => null,
                'canonical' => url('/cart'),
                'og_type' => 'website',
                'noindex' => true,
            ],
        ]);
    }

    /** Checkout: the form + place-order action is a Livewire island. */
    public function checkout(): View
    {
        abort_unless(Feature::ecommerce(), 404);

        return view('theme::checkout', [
            'themeSettings' => $this->themeSettings->active(),
            'seo' => [
                'title' => 'Checkout - '.config('app.name'),
                'description' => null,
                'canonical' => url('/checkout'),
                'og_type' => 'website',
                'noindex' => true,
            ],
        ]);
    }

    /**
     * Order confirmation page, looked up by the human-friendly order number.
     * The number alone isn't a secret (visible in browser history, could be
     * shared/guessed), so only the account owner or the guest who just placed
     * it (flagged in their own session at checkout) may view it.
     */
    public function orderConfirmation(string $number): View
    {
        abort_unless(Feature::ecommerce(), 404);

        $order = Order::with('items')->where('number', $number)->firstOrFail();

        $isOwner = ($order->user_id !== null && $order->user_id === auth()->id())
            || session('last_order_number') === $order->number;

        abort_unless($isOwner, 404);

        return view('theme::order-confirmation', [
            'order' => $order,
            'themeSettings' => $this->themeSettings->active(),
            'seo' => [
                'title' => 'Order Confirmed - '.config('app.name'),
                'description' => null,
                'canonical' => url('/order/'.$order->number),
                'og_type' => 'website',
                'noindex' => true,
            ],
        ]);
    }

    /** Resolve a published page by slug (catch-all for single-segment page URLs). */
    public function show(string $slug): View
    {
        $content = Page::published()->with('featuredImage')->where('slug', $slug)->firstOrFail();

        $template = $content->template === 'landing' ? 'theme::landing' : 'theme::page';

        return view($template, [
            'content' => $content,
            'renderedBlocks' => $this->blocks->render($content->blocks),
            'themeSettings' => $this->themeSettings->active(),
            'seo' => [
                'title' => ($content->meta_title ?: $content->title).' - '.config('app.name'),
                'description' => $content->meta_description,
                'canonical' => url('/'.$content->slug),
                'og_type' => 'website',
                'og_image' => $content->featuredImage?->url(),
            ],
        ]);
    }
}
