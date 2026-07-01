<?php

use App\Http\Controllers\FormController;
use App\Http\Controllers\FrontController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\ThemeAssetController;
use App\Livewire\Auth\Login;
use App\Livewire\Content\PageForm;
use App\Livewire\Content\PageList;
use App\Livewire\Content\PostForm;
use App\Livewire\Content\PostList;
use App\Livewire\Dashboard;
use App\Livewire\Forms\FormBuilder;
use App\Livewire\Forms\FormList;
use App\Livewire\Forms\FormSubmissions;
use App\Livewire\Install\Installer;
use App\Livewire\Media\MediaLibrary;
use App\Livewire\Menus\MenuBuilder;
use App\Livewire\Menus\MenuList;
use App\Livewire\Settings\WebsiteSettings;
use App\Livewire\Shop\OrderDetail;
use App\Livewire\Shop\OrderList;
use App\Livewire\Shop\PaymentSettingsForm;
use App\Livewire\Shop\PaymentVerificationList;
use App\Livewire\Shop\ProductCategoryForm;
use App\Livewire\Shop\ProductCategoryList;
use App\Livewire\Shop\ProductForm;
use App\Livewire\Shop\ProductList;
use App\Livewire\Shop\PromotionForm;
use App\Livewire\Shop\PromotionList;
use App\Livewire\Themes\ThemeAdmin;
use App\Livewire\Themes\ThemeCodeEditor;
use App\Livewire\Themes\ThemeSettings as ThemeSettingsAdmin;
use App\Livewire\Users\UserForm;
use App\Livewire\Users\UserList;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Theme static assets (css/js/images), path-jailed to themes/<slug>/assets/.
Route::get('/themes/{slug}/assets/{path}', [ThemeAssetController::class, 'show'])
    ->where('slug', '[a-z0-9][a-z0-9\-]*')
    ->where('path', '.*')
    ->name('theme.asset');

// Front-end: home + blog routes (MUST come before the page catch-all).
Route::get('/', [FrontController::class, 'home'])->name('home');
Route::get('/blog', [FrontController::class, 'blog'])->name('blog.index');
Route::get('/blog/{slug}', [FrontController::class, 'post'])
    ->where('slug', '[A-Za-z0-9][A-Za-z0-9\-]*')
    ->name('blog.show');
Route::get('/category/{slug}', [FrontController::class, 'category'])
    ->where('slug', '[A-Za-z0-9][A-Za-z0-9\-]*')
    ->name('category.show');
Route::get('/tag/{slug}', [FrontController::class, 'tag'])
    ->where('slug', '[A-Za-z0-9][A-Za-z0-9\-]*')
    ->name('tag.show');

// Storefront shop (ecommerce module - 404 when toggle off, handled in controller).
Route::get('/shop', [FrontController::class, 'shop'])->name('shop.index');
Route::get('/shop/{slug}', [FrontController::class, 'product'])
    ->where('slug', '[A-Za-z0-9][A-Za-z0-9\-]*')
    ->name('shop.product');
Route::get('/cart', [FrontController::class, 'cart'])->name('cart.index');
Route::get('/checkout', [FrontController::class, 'checkout'])->name('checkout.index');
Route::get('/order/{number}', [FrontController::class, 'orderConfirmation'])
    ->where('number', '[A-Za-z0-9\-]+')
    ->name('order.confirmation');

// Public form submission endpoint.
Route::post('/forms/{slug}', [FormController::class, 'submit'])
    ->where('slug', '[a-z0-9][a-z0-9\-]*')
    ->name('forms.submit');

// SEO: sitemap + robots (before the catch-all).
Route::get('/sitemap.xml', function () {
    $pages = Page::published()->orderBy('updated_at', 'desc')->get();
    $posts = Post::published()->orderBy('updated_at', 'desc')->get();

    return response()->view('sitemap', compact('pages', 'posts'))
        ->header('Content-Type', 'application/xml');
})->name('sitemap');

Route::get('/robots.txt', function () {
    return response("User-agent: *\nAllow: /\nSitemap: ".url('/sitemap.xml')."\n")
        ->header('Content-Type', 'text/plain');
})->name('robots');

// Lightweight health check — confirms the app boots and can answer.
Route::get('/health', function () {
    return response()->json([
        'app' => config('app.name'),
        'status' => 'ok',
        'time' => now()->toIso8601String(),
    ]);
})->name('health');

// First-run installer (only accessible when no users exist).
Route::get('/install', Installer::class)->name('install');

// Authentication (no public registration — admins are provisioned).
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', Login::class)->name('login');
});

Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout')->middleware('auth');

// Admin area (auth + staff-role gated).
Route::middleware(['auth', 'staff'])->prefix('admin')->group(function () {
    Route::get('/', Dashboard::class)->name('admin.dashboard');

    // Content: Pages
    Route::prefix('pages')->name('admin.pages.')->group(function () {
        Route::get('/', PageList::class)->name('index');
        Route::get('/create', PageForm::class)->name('create');
        Route::get('/{id}/edit', PageForm::class)->name('edit');
    });

    // Content: Posts
    Route::prefix('posts')->name('admin.posts.')->group(function () {
        Route::get('/', PostList::class)->name('index');
        Route::get('/create', PostForm::class)->name('create');
        Route::get('/{id}/edit', PostForm::class)->name('edit');
    });

    // Media Library
    Route::get('/media', MediaLibrary::class)->name('admin.media.index');

    // Forms
    Route::prefix('forms')->name('admin.forms.')->group(function () {
        Route::get('/', FormList::class)->name('index');
        Route::get('/create', FormBuilder::class)->name('create');
        Route::get('/{id}/edit', FormBuilder::class)->name('edit');
        Route::get('/{id}/submissions', FormSubmissions::class)->name('submissions');
    });

    // Themes
    Route::get('/themes', ThemeAdmin::class)->name('admin.themes.index');
    Route::get('/themes/settings', ThemeSettingsAdmin::class)->name('admin.themes.settings');
    Route::get('/themes/code', ThemeCodeEditor::class)->name('admin.themes.code');

    // Website settings (general / reading).
    Route::get('/settings', WebsiteSettings::class)->name('admin.settings.index');

    // Users & roles
    Route::prefix('users')->name('admin.users.')->group(function () {
        Route::get('/', UserList::class)->name('index');
        Route::get('/create', UserForm::class)->name('create');
        Route::get('/{id}/edit', UserForm::class)->name('edit');
    });

    // Menus
    Route::prefix('menus')->name('admin.menus.')->group(function () {
        Route::get('/', MenuList::class)->name('index');
        Route::get('/create', MenuBuilder::class)->name('create');
        Route::get('/{id}/edit', MenuBuilder::class)->name('edit');
    });

    // Ecommerce module — gated by the `ecommerce` feature toggle.
    Route::middleware('ecommerce')->prefix('shop')->name('admin.shop.')->group(function () {
        // Products
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', ProductList::class)->name('index');
            Route::get('/create', ProductForm::class)->name('create');
            Route::get('/{id}/edit', ProductForm::class)->name('edit');
        });

        // Product categories
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', ProductCategoryList::class)->name('index');
            Route::get('/create', ProductCategoryForm::class)->name('create');
            Route::get('/{id}/edit', ProductCategoryForm::class)->name('edit');
        });

        // Orders
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', OrderList::class)->name('index');
            Route::get('/{id}', OrderDetail::class)->name('show');
        });

        // Promotions & coupons
        Route::prefix('promotions')->name('promotions.')->group(function () {
            Route::get('/', PromotionList::class)->name('index');
            Route::get('/create', PromotionForm::class)->name('create');
            Route::get('/{id}/edit', PromotionForm::class)->name('edit');
        });

        // Payments — manual verification queue + settings.
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', PaymentVerificationList::class)->name('index');
            Route::get('/settings', PaymentSettingsForm::class)->name('settings');
            Route::get('/{payment}/proof', [PaymentProofController::class, 'show'])->name('proof');
        });
    });
});

// Front-end content catch-all (MUST stay last). Single-segment slugs only, so it
// never shadows /admin/*, /install, etc. Resolves published pages + posts by slug.
Route::get('/{slug}', [FrontController::class, 'show'])
    ->where('slug', '[A-Za-z0-9][A-Za-z0-9\-]*')
    ->name('content.show');
