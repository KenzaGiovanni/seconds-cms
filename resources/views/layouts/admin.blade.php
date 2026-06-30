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
            <nav class="flex-1 space-y-1 px-3 py-2">
                <a href="{{ route('admin.dashboard') }}"
                   @class([
                       'flex items-center gap-3 rounded-[var(--radius-btn)] px-3 py-2 text-sm font-medium transition',
                       'bg-accent/10 text-accent' => request()->routeIs('admin.dashboard'),
                       'text-muted hover:bg-soft hover:text-ink' => ! request()->routeIs('admin.dashboard'),
                   ])>
                    <span class="font-display">Dashboard</span>
                </a>

                {{-- Content --}}
                @can(\App\Enums\Permission::ContentManage->value)
                    <a href="{{ route('admin.pages.index') }}"
                       @class([
                           'flex items-center gap-3 rounded-[var(--radius-btn)] px-3 py-2 text-sm font-medium transition',
                           'bg-accent/10 text-accent' => request()->routeIs('admin.pages.*'),
                           'text-muted hover:bg-soft hover:text-ink' => ! request()->routeIs('admin.pages.*'),
                       ])>
                        <span class="font-display">Pages</span>
                    </a>
                    <a href="{{ route('admin.posts.index') }}"
                       @class([
                           'flex items-center gap-3 rounded-[var(--radius-btn)] px-3 py-2 text-sm font-medium transition',
                           'bg-accent/10 text-accent' => request()->routeIs('admin.posts.*'),
                           'text-muted hover:bg-soft hover:text-ink' => ! request()->routeIs('admin.posts.*'),
                       ])>
                        <span class="font-display">Posts</span>
                    </a>
                    <a href="{{ route('admin.media.index') }}"
                       @class([
                           'flex items-center gap-3 rounded-[var(--radius-btn)] px-3 py-2 text-sm font-medium transition',
                           'bg-accent/10 text-accent' => request()->routeIs('admin.media.*'),
                           'text-muted hover:bg-soft hover:text-ink' => ! request()->routeIs('admin.media.*'),
                       ])>
                        <span class="font-display">Media</span>
                    </a>
                    <a href="{{ route('admin.menus.index') }}"
                       @class([
                           'flex items-center gap-3 rounded-[var(--radius-btn)] px-3 py-2 text-sm font-medium transition',
                           'bg-accent/10 text-accent' => request()->routeIs('admin.menus.*'),
                           'text-muted hover:bg-soft hover:text-ink' => ! request()->routeIs('admin.menus.*'),
                       ])>
                        <span class="font-display">Menus</span>
                    </a>
                @endcan

                {{-- Themes --}}
                @can(\App\Enums\Permission::ThemesManage->value)
                    <a href="{{ route('admin.themes.settings') }}"
                       @class([
                           'flex items-center gap-3 rounded-[var(--radius-btn)] px-3 py-2 text-sm font-medium transition',
                           'bg-accent/10 text-accent' => request()->routeIs('admin.themes.*'),
                           'text-muted hover:bg-soft hover:text-ink' => ! request()->routeIs('admin.themes.*'),
                       ])>
                        <span class="font-display">Theme Settings</span>
                    </a>
                @endcan

                {{-- Ecommerce nav (visible only when the toggle is on) --}}
                @if(\App\Support\Feature::ecommerce())
                    <a href="{{ route('admin.shop.products') }}"
                       @class([
                           'flex items-center gap-3 rounded-[var(--radius-btn)] px-3 py-2 text-sm font-medium transition',
                           'bg-accent/10 text-accent' => request()->routeIs('admin.shop.products'),
                           'text-muted hover:bg-soft hover:text-ink' => ! request()->routeIs('admin.shop.products'),
                       ])>
                        <span class="font-display">Products</span>
                    </a>
                    <a href="{{ route('admin.shop.orders') }}"
                       @class([
                           'flex items-center gap-3 rounded-[var(--radius-btn)] px-3 py-2 text-sm font-medium transition',
                           'bg-accent/10 text-accent' => request()->routeIs('admin.shop.orders'),
                           'text-muted hover:bg-soft hover:text-ink' => ! request()->routeIs('admin.shop.orders'),
                       ])>
                        <span class="font-display">Orders</span>
                    </a>
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

            <main class="flex-1 p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
