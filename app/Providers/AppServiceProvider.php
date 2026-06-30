<?php

namespace App\Providers;

use App\Models\Menu;
use App\Models\User;
use App\Support\BlockRegistry;
use App\Support\ThemeManager;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThemeManager::class);
        $this->app->singleton(BlockRegistry::class);
    }

    public function boot(): void
    {
        // Super-admin (owner/installer) bypasses every permission check.
        Gate::before(function (User $user, string $ability) {
            return $user->isSuperAdmin() ? true : null;
        });

        // Register the active theme's Blade view namespace (skip if table not yet created).
        try {
            $this->app->make(ThemeManager::class)->boot();
        } catch (QueryException) {
            // Themes table doesn't exist yet (pre-migration / fresh install).
        }

        // @menu('location') — renders the menu assigned to a theme location.
        Blade::directive('menu', function (string $expression) {
            return "<?php echo view('theme::partials.menu', ['menu' => \App\Models\Menu::forLocation({$expression})])->render(); ?>";
        });

        // @form('slug') — renders a form by slug through the active theme.
        Blade::directive('form', function (string $expression) {
            return "<?php echo \App\Support\FormRenderer::render({$expression}); ?>";
        });
    }
}
