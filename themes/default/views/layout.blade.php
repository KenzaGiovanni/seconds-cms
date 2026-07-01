{{-- Default theme base layout - Seconds Default v1.0 --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- SEO meta --}}
    <title>{{ $seo['title'] ?? ($title ?? config('app.name')) }}</title>
    @if(!empty($seo['description']))
        <meta name="description" content="{{ $seo['description'] }}">
    @endif
    @if(!empty($seo['canonical']))
        <link rel="canonical" href="{{ $seo['canonical'] }}">
    @endif
    @if(!empty($seo['noindex']))
        <meta name="robots" content="noindex">
    @endif

    {{-- OpenGraph --}}
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $seo['title'] ?? ($title ?? config('app.name')) }}">
    @if(!empty($seo['description']))
        <meta property="og:description" content="{{ $seo['description'] }}">
    @endif
    <meta property="og:type" content="{{ $seo['og_type'] ?? 'website' }}">
    @if(!empty($seo['canonical']))
        <meta property="og:url" content="{{ $seo['canonical'] }}">
    @endif
    @if(!empty($seo['og_image']))
        <meta property="og:image" content="{{ $seo['og_image'] }}">
    @endif

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&family=Space+Grotesk:wght@500;600&display=swap" rel="stylesheet">

    @php($accent = $themeSettings['primary_color'] ?? '#16513F')
    {{-- Theme stylesheet (themes/<active>/assets/css/style.css) --}}
    <link rel="stylesheet" href="@themeAsset('css/style.css')">

    {{-- Dynamic accent from the theme's primary_color setting (overrides the stylesheet defaults) --}}
    <style>
        :root {
            --accent: {{ $accent }};
            --accent2: color-mix(in srgb, {{ $accent }} 80%, white);
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <header class="site-header">
        <div class="wrap">
            <a href="{{ url('/') }}" class="site-wordmark">{{ config('app.name') }}<span>.</span></a>
            @menu('primary')
            @if(\App\Support\Feature::ecommerce())
                <div class="header-shop">
                    <a href="{{ route('shop.index') }}"
                       @class(['header-shop-link', 'active' => request()->routeIs('shop.*')])>Shop</a>
                    @livewire('shop.mini-cart')
                </div>
            @endif
        </div>
    </header>

    {{-- Main --}}
    <main class="site-main">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="site-footer">
        <div class="wrap">
            <a href="{{ url('/') }}" class="footer-wordmark">{{ config('app.name') }}<span>.</span></a>
            @menu('footer')
            <p class="footer-copy">
                @if(!empty($themeSettings['footer_text']))
                    {{ $themeSettings['footer_text'] }}
                @else
                    &copy; {{ date('Y') }} {{ config('app.name') }}
                @endif
            </p>
        </div>
    </footer>

</body>
</html>
