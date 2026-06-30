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
    <style>
        :root {
            --bg: #FFFFFF;
            --soft: #F4F5F3;
            --ink: #101413;
            --muted: #6B716E;
            --line: #E6E8E5;
            --accent: {{ $accent }};
            --accent2: color-mix(in srgb, {{ $accent }} 80%, white);
            --radius-card: 18px;
            --radius-btn: 12px;
            --radius-pill: 999px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            font-weight: 400;
            font-size: 16px;
            line-height: 1.7;
            color: var(--ink);
            background: var(--bg);
            -webkit-font-smoothing: antialiased;
        }

        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            font-weight: 600;
            line-height: 1.15;
            color: var(--ink);
            letter-spacing: -0.02em;
        }

        h1 { font-size: clamp(2rem, 4vw, 2.875rem); }
        h2 { font-size: clamp(1.5rem, 3vw, 2.125rem); }
        h3 { font-size: 1.25rem; }
        h4 { font-size: 1.0625rem; }

        p { line-height: 1.75; color: var(--ink); }

        img { max-width: 100%; display: block; }

        .wrap {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2.5rem;
        }

        @media (max-width: 640px) {
            .wrap { padding: 0 1.25rem; }
        }

        /* --- Header / Nav --- */
        .site-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--line);
        }

        .site-header .wrap {
            display: flex;
            align-items: center;
            height: 64px;
            gap: 2rem;
        }

        .site-wordmark {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            font-weight: 600;
            font-size: 1.25rem;
            letter-spacing: -0.03em;
            color: var(--ink);
            text-decoration: none;
            flex-shrink: 0;
        }
        .site-wordmark:hover { text-decoration: none; }
        .site-wordmark span { color: var(--accent); }

        .primary-nav {
            display: flex;
            align-items: center;
            gap: 0.125rem;
            list-style: none;
            margin-left: auto;
        }

        .primary-nav a {
            display: block;
            padding: 0.4rem 0.875rem;
            font-size: 0.9375rem;
            font-weight: 500;
            color: var(--muted);
            border-radius: var(--radius-btn);
            transition: color 0.15s, background 0.15s;
            text-decoration: none;
        }
        .primary-nav a:hover { color: var(--ink); background: var(--soft); }
        .primary-nav a.active { color: var(--accent); background: color-mix(in srgb, var(--accent) 8%, transparent); }

        /* --- Main content --- */
        .site-main {
            min-height: calc(100vh - 64px - 80px);
        }

        /* --- Hero --- */
        .hero {
            padding: 5rem 0 4rem;
            border-bottom: 1px solid var(--line);
        }

        .hero h1 {
            font-size: clamp(2.5rem, 6vw, 4.625rem);
            letter-spacing: -0.035em;
            line-height: 1.05;
            max-width: 700px;
            margin-bottom: 1.25rem;
        }

        .hero p {
            font-size: 1.1875rem;
            color: var(--muted);
            max-width: 520px;
            line-height: 1.6;
        }

        /* --- Section --- */
        .section {
            padding: 4rem 0;
        }

        .section-header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            margin-bottom: 2rem;
            gap: 1rem;
        }

        .section-header h2 {
            font-size: 1.5rem;
        }

        .section-link {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--accent);
            white-space: nowrap;
        }

        /* --- Post grid --- */
        .post-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .post-card {
            background: var(--soft);
            border-radius: var(--radius-card);
            padding: 1.75rem;
            transition: transform 0.2s;
        }

        .post-card:hover {
            transform: translateY(-3px);
        }

        .post-card-meta {
            font-size: 0.8125rem;
            color: var(--muted);
            margin-bottom: 0.625rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .post-card h2, .post-card h3 {
            font-size: 1.1875rem;
            margin-bottom: 0.75rem;
            letter-spacing: -0.015em;
        }

        .post-card h2 a, .post-card h3 a {
            color: var(--ink);
            text-decoration: none;
        }
        .post-card h2 a:hover, .post-card h3 a:hover { color: var(--accent); }

        .post-card p {
            font-size: 0.9375rem;
            color: var(--muted);
            line-height: 1.65;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .post-card-footer {
            margin-top: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* --- Pill / badge --- */
        .pill {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.25rem 0.6875rem;
            border-radius: var(--radius-pill);
            background: color-mix(in srgb, var(--accent) 10%, transparent);
            color: var(--accent);
            text-decoration: none;
        }
        .pill:hover { text-decoration: none; background: color-mix(in srgb, var(--accent) 18%, transparent); }

        .pill-muted {
            background: var(--soft);
            color: var(--muted);
            border: 1px solid var(--line);
        }

        /* --- Article layout (page / post) --- */
        .article-wrap {
            max-width: 720px;
            margin: 0 auto;
            padding: 3.5rem 2.5rem;
        }

        @media (max-width: 640px) {
            .article-wrap { padding: 2.5rem 1.25rem; }
        }

        .article-header {
            margin-bottom: 2.5rem;
        }

        .article-header h1 {
            margin-bottom: 1rem;
        }

        .article-meta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
            font-size: 0.875rem;
            color: var(--muted);
            margin-top: 0.875rem;
        }

        .article-meta time { color: var(--muted); }

        .article-featured-image {
            border-radius: var(--radius-card);
            overflow: hidden;
            margin-bottom: 2.5rem;
        }

        .article-featured-image img {
            width: 100%;
            height: auto;
        }

        .article-body { }

        /* Prose block styles */
        .article-body p {
            margin-bottom: 1.25rem;
            font-size: 1.0625rem;
            line-height: 1.75;
        }

        .article-body h1,
        .article-body h2,
        .article-body h3,
        .article-body h4 {
            margin-top: 2rem;
            margin-bottom: 0.875rem;
        }

        .article-body h2 { font-size: 1.625rem; }
        .article-body h3 { font-size: 1.25rem; }

        .article-body img {
            border-radius: var(--radius-card);
            margin: 1.5rem 0;
        }

        .article-body figure {
            margin: 1.5rem 0;
        }

        .article-body figcaption {
            font-size: 0.875rem;
            color: var(--muted);
            margin-top: 0.5rem;
            text-align: center;
        }

        .article-body hr {
            border: none;
            border-top: 1px solid var(--line);
            margin: 2rem 0;
        }

        /* --- Article footer (tags) --- */
        .article-tags {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid var(--line);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .article-tags .label {
            font-size: 0.8125rem;
            color: var(--muted);
            font-weight: 500;
        }

        /* --- Archive heading --- */
        .archive-header {
            padding: 3rem 0 2rem;
            border-bottom: 1px solid var(--line);
            margin-bottom: 3rem;
        }

        .archive-header h1 {
            margin-bottom: 0.5rem;
        }

        .archive-header p {
            color: var(--muted);
        }

        /* --- Empty state --- */
        .empty-state {
            padding: 5rem 0;
            text-align: center;
            color: var(--muted);
        }

        /* --- Pagination --- */
        .pagination-wrap {
            margin-top: 3rem;
            display: flex;
            justify-content: center;
        }

        .pagination-wrap nav {
            display: flex;
            gap: 0.375rem;
        }

        /* --- Footer --- */
        .site-footer {
            border-top: 1px solid var(--line);
            padding: 2.5rem 0;
            margin-top: 4rem;
        }

        .site-footer .wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .footer-wordmark {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            font-weight: 600;
            font-size: 1rem;
            color: var(--ink);
            text-decoration: none;
            letter-spacing: -0.025em;
        }
        .footer-wordmark span { color: var(--accent); }
        .footer-wordmark:hover { text-decoration: none; }

        .footer-copy {
            font-size: 0.8125rem;
            color: var(--muted);
        }

        .footer-nav {
            display: flex;
            gap: 1.25rem;
            list-style: none;
        }

        .footer-nav a {
            font-size: 0.875rem;
            color: var(--muted);
            text-decoration: none;
        }
        .footer-nav a:hover { color: var(--ink); }

        /* --- Forms --- */
        .seconds-form { max-width: 540px; }
        .seconds-form-hp { position: absolute !important; left: -9999px !important; height: 0; overflow: hidden; }
        .seconds-form-field { margin-bottom: 1.25rem; }
        .seconds-form-field > label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--ink);
            margin-bottom: 0.375rem;
        }
        .seconds-form-field .req { color: var(--accent); }
        .seconds-form input[type="text"],
        .seconds-form input[type="email"],
        .seconds-form input[type="number"],
        .seconds-form textarea,
        .seconds-form select {
            width: 100%;
            font: inherit;
            font-size: 0.9375rem;
            color: var(--ink);
            background: var(--soft);
            border: 1px solid var(--line);
            border-radius: var(--radius-btn);
            padding: 0.625rem 0.75rem;
        }
        .seconds-form input:focus,
        .seconds-form textarea:focus,
        .seconds-form select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 1px var(--accent);
        }
        .seconds-form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9375rem;
            font-weight: 400;
        }
        .seconds-form-check input { width: auto; }
        .seconds-form-error {
            margin-top: 0.375rem;
            font-size: 0.8125rem;
            color: #b91c1c;
        }
        .seconds-form-success {
            margin-bottom: 1.25rem;
            padding: 0.75rem 1rem;
            border-radius: var(--radius-btn);
            background: color-mix(in srgb, var(--accent) 10%, transparent);
            color: var(--accent);
            font-size: 0.9375rem;
        }
        .seconds-form-submit {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            font-weight: 500;
            font-size: 0.9375rem;
            color: #fff;
            background: var(--accent);
            border: none;
            border-radius: var(--radius-btn);
            padding: 0.625rem 1.5rem;
            cursor: pointer;
            transition: background 0.15s;
        }
        .seconds-form-submit:hover { background: var(--accent2); }
    </style>
</head>
<body>

    {{-- Header --}}
    <header class="site-header">
        <div class="wrap">
            <a href="{{ url('/') }}" class="site-wordmark">{{ config('app.name') }}<span>.</span></a>
            @menu('primary')
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
