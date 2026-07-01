<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} · {{ config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-soft text-ink antialiased font-sans">
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="hidden w-60 shrink-0 flex-col border-r border-line bg-bg lg:flex">
            <div class="flex h-16 items-center px-6">
                <a href="{{ route('admin.dashboard') }}" class="font-display text-xl font-bold tracking-tight text-ink">
                    Seconds<span class="text-accent">.</span>
                </a>
            </div>
            @php
                $navItem = 'flex items-center gap-3 rounded-[var(--radius-btn)] px-3 py-2 text-sm font-medium transition font-display';
                $navIdle = 'text-muted hover:bg-soft hover:text-ink';
                $navActive = 'bg-accent/10 text-accent';
                $sectionLabel = 'px-3 pb-1.5 pt-2 text-[11px] font-semibold uppercase tracking-wider text-muted/70 font-display';
            @endphp
            <nav class="flex-1 space-y-0.5 px-3 py-3">
                {{-- Overview --}}
                <a href="{{ route('admin.dashboard') }}"
                   @class([$navItem, $navActive => request()->routeIs('admin.dashboard'), $navIdle => ! request()->routeIs('admin.dashboard')])>
                    Dashboard
                </a>

                {{-- Content --}}
                @can(\App\Enums\Permission::ContentManage->value)
                    <p class="{{ $sectionLabel }} mt-4">Content</p>
                    <a href="{{ route('admin.pages.index') }}"
                       @class([$navItem, $navActive => request()->routeIs('admin.pages.*'), $navIdle => ! request()->routeIs('admin.pages.*')])>
                        Pages
                    </a>
                    <a href="{{ route('admin.posts.index') }}"
                       @class([$navItem, $navActive => request()->routeIs('admin.posts.*'), $navIdle => ! request()->routeIs('admin.posts.*')])>
                        Posts
                    </a>
                    <a href="{{ route('admin.media.index') }}"
                       @class([$navItem, $navActive => request()->routeIs('admin.media.*'), $navIdle => ! request()->routeIs('admin.media.*')])>
                        Media
                    </a>
                    <a href="{{ route('admin.menus.index') }}"
                       @class([$navItem, $navActive => request()->routeIs('admin.menus.*'), $navIdle => ! request()->routeIs('admin.menus.*')])>
                        Menus
                    </a>
                    <a href="{{ route('admin.forms.index') }}"
                       @class([$navItem, $navActive => request()->routeIs('admin.forms.*'), $navIdle => ! request()->routeIs('admin.forms.*')])>
                        Forms
                    </a>
                @endcan

                {{-- Appearance --}}
                @can(\App\Enums\Permission::ThemesManage->value)
                    <p class="{{ $sectionLabel }} mt-4">Appearance</p>
                    <a href="{{ route('admin.themes.index') }}"
                       @class([$navItem, $navActive => request()->routeIs('admin.themes.index'), $navIdle => ! request()->routeIs('admin.themes.index')])>
                        Themes
                    </a>
                    <a href="{{ route('admin.themes.settings') }}"
                       @class([$navItem, $navActive => request()->routeIs('admin.themes.settings'), $navIdle => ! request()->routeIs('admin.themes.settings')])>
                        Customize
                    </a>
                    @if(\App\Support\SiteSettings::themeEditorEnabled())
                        @can(\App\Enums\Permission::ThemesEditCode->value)
                            <a href="{{ route('admin.themes.code') }}"
                               @class([$navItem, $navActive => request()->routeIs('admin.themes.code'), $navIdle => ! request()->routeIs('admin.themes.code')])>
                                Theme Code
                            </a>
                        @endcan
                    @endif
                @endcan

                {{-- Settings --}}
                @canany([\App\Enums\Permission::SettingsManage->value, \App\Enums\Permission::UsersManage->value])
                    <p class="{{ $sectionLabel }} mt-4">Settings</p>
                    @can(\App\Enums\Permission::SettingsManage->value)
                        <a href="{{ route('admin.settings.index') }}"
                           @class([$navItem, $navActive => request()->routeIs('admin.settings.*'), $navIdle => ! request()->routeIs('admin.settings.*')])>
                            Website
                        </a>
                    @endcan
                    @can(\App\Enums\Permission::UsersManage->value)
                        <a href="{{ route('admin.users.index') }}"
                           @class([$navItem, $navActive => request()->routeIs('admin.users.*'), $navIdle => ! request()->routeIs('admin.users.*')])>
                            Users
                        </a>
                    @endcan
                @endcanany

                {{-- Shop (visible only when the ecommerce toggle is on + user has shop access) --}}
                @if(\App\Support\Feature::ecommerce())
                    @canany([\App\Enums\Permission::ProductsManage->value, \App\Enums\Permission::OrdersManage->value, \App\Enums\Permission::PromotionsManage->value])
                        <p class="{{ $sectionLabel }} mt-4">Shop</p>
                        @can(\App\Enums\Permission::ProductsManage->value)
                            <a href="{{ route('admin.shop.products.index') }}"
                               @class([$navItem, $navActive => request()->routeIs('admin.shop.products.*'), $navIdle => ! request()->routeIs('admin.shop.products.*')])>
                                Products
                            </a>
                            <a href="{{ route('admin.shop.categories.index') }}"
                               @class([$navItem, $navActive => request()->routeIs('admin.shop.categories.*'), $navIdle => ! request()->routeIs('admin.shop.categories.*')])>
                                Categories
                            </a>
                        @endcan
                        @can(\App\Enums\Permission::OrdersManage->value)
                            <a href="{{ route('admin.shop.orders.index') }}"
                               @class([$navItem, $navActive => request()->routeIs('admin.shop.orders.*'), $navIdle => ! request()->routeIs('admin.shop.orders.*')])>
                                Orders
                            </a>
                            <a href="{{ route('admin.shop.payments.index') }}"
                               @class([$navItem, $navActive => request()->routeIs('admin.shop.payments.*'), $navIdle => ! request()->routeIs('admin.shop.payments.*')])>
                                Payments
                            </a>
                        @endcan
                        @can(\App\Enums\Permission::PromotionsManage->value)
                            <a href="{{ route('admin.shop.promotions.index') }}"
                               @class([$navItem, $navActive => request()->routeIs('admin.shop.promotions.*'), $navIdle => ! request()->routeIs('admin.shop.promotions.*')])>
                                Promotions
                            </a>
                        @endcan
                    @endcanany
                @endif
            </nav>
        </aside>

        {{-- Main --}}
        <div class="flex min-w-0 flex-1 flex-col">
            {{-- Topbar --}}
            <header class="flex h-16 items-center justify-between border-b border-line bg-bg px-6">
                <div class="font-display text-sm font-medium text-muted lg:hidden">
                    Seconds<span class="text-accent">.</span>
                </div>
                <div class="ml-auto flex items-center gap-4">
                    <span class="text-sm text-muted">{{ auth()->user()?->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="rounded-[var(--radius-btn)] border border-line px-3 py-1.5 font-display text-xs font-medium text-ink transition hover:bg-soft">
                            Sign out
                        </button>
                    </form>
                </div>
            </header>

            <main class="flex-1 px-6 py-8 lg:px-10 lg:py-10">
                <div class="mx-auto w-full max-w-6xl">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</body>
</html>
