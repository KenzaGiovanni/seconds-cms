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

        /* --- Section blocks (contained, sit inside the content column) --- */
        .block-btn {
            display: inline-block;
            font-family: 'Space Grotesk', system-ui, sans-serif;
            font-weight: 500;
            font-size: 0.9375rem;
            color: #fff;
            background: var(--accent);
            border-radius: var(--radius-btn);
            padding: 0.625rem 1.5rem;
            text-decoration: none;
            transition: background 0.15s;
        }
        .block-btn:hover { background: var(--accent2); text-decoration: none; }

        .block-hero {
            border-radius: var(--radius-card);
            background: var(--soft);
            padding: 3rem 2.5rem;
            margin: 1.5rem 0;
            background-size: cover;
            background-position: center;
        }
        .block-hero-image { color: #fff; }
        .block-hero-image h1, .block-hero-image p { color: #fff; }
        .block-hero h1 { font-size: clamp(1.875rem, 4vw, 2.75rem); margin-bottom: 0.875rem; letter-spacing: -0.03em; }
        .block-hero p { font-size: 1.0625rem; color: var(--muted); max-width: 36rem; margin-bottom: 1.5rem; }
        .block-hero-image p { color: rgba(255,255,255,0.85); }

        .feature-grid { margin: 2rem 0; }
        .feature-grid-heading { font-size: 1.5rem; margin-bottom: 1.5rem; letter-spacing: -0.02em; }
        .feature-grid-items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
        }
        .feature-card {
            background: var(--soft);
            border-radius: var(--radius-card);
            padding: 1.5rem;
        }
        .feature-icon { font-size: 1.75rem; margin-bottom: 0.75rem; line-height: 1; }
        .feature-card h3 { font-size: 1.0625rem; margin-bottom: 0.5rem; }
        .feature-card p { font-size: 0.9375rem; color: var(--muted); line-height: 1.6; }

        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 0.75rem;
            margin: 2rem 0;
        }
        .gallery-item { margin: 0; }
        .gallery-item img { width: 100%; border-radius: var(--radius-card); }
        .gallery-item figcaption { font-size: 0.8125rem; color: var(--muted); margin-top: 0.5rem; }

        .block-cta {
            margin: 2rem 0;
        }
        .block-cta-inner {
            border-radius: var(--radius-card);
            background: color-mix(in srgb, var(--accent) 8%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 18%, transparent);
            padding: 2.5rem 2rem;
            text-align: center;
        }
        .block-cta-inner h2 { font-size: 1.625rem; margin-bottom: 0.625rem; letter-spacing: -0.02em; }
        .block-cta-inner p { color: var(--muted); max-width: 32rem; margin: 0 auto 1.5rem; }

        /* --- Rich text block --- */
        .richtext-content p { margin-bottom: 1.25rem; font-size: 1.0625rem; line-height: 1.75; }
        .richtext-content h2 { font-size: 1.625rem; margin-top: 2rem; margin-bottom: 0.875rem; }
        .richtext-content h3 { font-size: 1.25rem; margin-top: 1.5rem; margin-bottom: 0.75rem; }
        .richtext-content ul, .richtext-content ol { padding-left: 1.5rem; margin-bottom: 1.25rem; }
        .richtext-content li { margin-bottom: 0.375rem; line-height: 1.7; }
        .richtext-content a { color: var(--accent); }
        .richtext-content strong { font-weight: 600; }
        .richtext-content blockquote {
            border-left: 3px solid var(--accent);
            padding-left: 1.25rem;
            margin: 1.5rem 0;
            color: var(--muted);
            font-style: italic;
        }

        /* --- Testimonials --- */
        .testimonials-block { margin: 2rem 0; }
        .testimonials-heading { font-size: 1.5rem; margin-bottom: 1.5rem; letter-spacing: -0.02em; }
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.25rem;
        }
        .testimonial-card {
            background: var(--soft);
            border-radius: var(--radius-card);
            padding: 1.75rem;
            margin: 0;
        }
        .testimonial-quote {
            font-size: 1rem;
            line-height: 1.75;
            color: var(--ink);
            margin-bottom: 1.25rem;
            font-style: italic;
        }
        .testimonial-footer { display: flex; flex-direction: column; gap: 0.125rem; }
        .testimonial-name {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--ink);
            font-style: normal;
        }
        .testimonial-role { font-size: 0.8125rem; color: var(--muted); }

        /* --- Landing page layout --- */
        .landing-blocks { width: 100%; }

        .landing-blocks .block-hero {
            border-radius: 0;
            margin: 0;
            padding: 6rem 0;
        }
        .landing-blocks .block-hero .block-hero-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2.5rem;
        }
        .landing-blocks .feature-grid {
            max-width: 1200px;
            margin: 0 auto;
            padding: 4rem 2.5rem;
        }
        .landing-blocks .block-cta {
            max-width: 1200px;
            margin: 0 auto;
            padding: 4rem 2.5rem;
        }
        .landing-blocks .gallery {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 2.5rem;
        }
        .landing-blocks .testimonials-block {
            max-width: 1200px;
            margin: 0 auto;
            padding: 4rem 2.5rem;
        }
        .landing-blocks .richtext-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 2.5rem;
        }
        .landing-prose { padding: 3rem 0 4rem; }

        @media (max-width: 640px) {
            .landing-blocks .block-hero .block-hero-inner,
            .landing-blocks .feature-grid,
            .landing-blocks .block-cta,
            .landing-blocks .gallery,
            .landing-blocks .testimonials-block,
            .landing-blocks .richtext-content { padding-left: 1.25rem; padding-right: 1.25rem; }
        }

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

        /* --- Shop filters --- */
        .shop-filters {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 2.5rem;
        }
        .pill.active {
            background: var(--accent);
            color: #fff;
        }
        .pill.active:hover { background: var(--accent2); }

        /* --- Product card (shop index) --- */
        .product-card {
            display: block;
            background: var(--soft);
            border-radius: var(--radius-card);
            overflow: hidden;
            margin-bottom: 1.5rem;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .product-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); text-decoration: none; }
        .product-card-img { aspect-ratio: 4/3; overflow: hidden; background: var(--line); }
        .product-card-img img { width: 100%; height: 100%; object-fit: cover; }
        .product-card-img--empty { background: var(--line); }
        .product-card-body { padding: 1.25rem 1.5rem 1.5rem; }
        .product-card-name { font-size: 1.125rem; font-family: 'Space Grotesk', sans-serif; font-weight: 600; margin-bottom: 0.25rem; color: var(--ink); }
        .product-card-cats { font-size: 0.8125rem; color: var(--muted); margin-bottom: 0.5rem; }
        .product-card-price { font-size: 1rem; font-weight: 600; font-family: 'Space Grotesk', sans-serif; color: var(--accent); margin-bottom: 0.5rem; }
        .badge { display: inline-block; font-size: 0.75rem; font-weight: 500; padding: 0.2rem 0.6rem; border-radius: var(--radius-pill); }
        .badge--out { background: #fee2e2; color: #991b1b; }
        .badge--low { background: #fef9c3; color: #854d0e; }

        /* Make shop index use a card grid */
        .wrap .product-card { display: inline-block; vertical-align: top; width: 100%; }
        @supports (display: grid) {
            .wrap:has(.product-card) {
                display: block;
            }
        }

        /* Product grid: 3-col on large, 2-col medium, 1-col mobile */
        .shop-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 4rem;
        }
        @media (max-width: 900px) { .shop-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 560px) { .shop-grid { grid-template-columns: 1fr; } }

        /* --- Product detail page --- */
        .product-detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            padding: 4rem 0;
            align-items: start;
        }
        @media (max-width: 768px) {
            .product-detail { grid-template-columns: 1fr; gap: 2rem; padding: 2rem 0; }
        }
        .product-detail-image { border-radius: var(--radius-card); overflow: hidden; }
        .product-detail-image img { width: 100%; height: auto; }
        .product-breadcrumb { font-size: 0.8125rem; color: var(--muted); margin-bottom: 1rem; }
        .product-breadcrumb a { color: var(--muted); }
        .product-breadcrumb a:hover { color: var(--accent); }
        .product-detail-name { font-size: clamp(1.5rem, 3vw, 2.25rem); margin-bottom: 0.75rem; }
        .product-detail-desc { font-size: 1.0625rem; color: var(--muted); line-height: 1.7; margin-bottom: 1.75rem; }
        .product-detail-content { margin-top: 2.5rem; border-top: 1px solid var(--line); padding-top: 2rem; }

        /* Livewire product-detail widget */
        .product-detail-widget { }
        .product-price { font-size: 1.625rem; font-family: 'Space Grotesk', sans-serif; font-weight: 700; color: var(--accent); margin-bottom: 1.25rem; letter-spacing: -0.02em; }
        .product-variants { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.5rem; }
        .variant-btn {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.4rem 1rem;
            border-radius: var(--radius-pill);
            border: 1.5px solid var(--line);
            background: transparent;
            color: var(--ink);
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
        }
        .variant-btn:hover { border-color: var(--accent); }
        .variant-btn--selected { border-color: var(--accent); background: color-mix(in srgb, var(--accent) 8%, transparent); color: var(--accent); }
        .stock-badge { display: inline-block; font-size: 0.8125rem; margin-bottom: 1rem; padding: 0.3rem 0.75rem; border-radius: var(--radius-pill); }
        .stock-badge--out { background: #fee2e2; color: #991b1b; }
        .btn-add-to-cart {
            width: 100%;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            background: var(--accent);
            border: none;
            border-radius: var(--radius-btn);
            padding: 0.875rem 2rem;
            cursor: pointer;
            transition: background 0.15s, opacity 0.15s;
            letter-spacing: -0.01em;
        }
        .btn-add-to-cart:hover:not(:disabled) { background: var(--accent2); }
        .btn-add-to-cart:disabled { opacity: 0.45; cursor: not-allowed; }

        .add-to-cart-row { display: flex; gap: 0.75rem; align-items: stretch; }
        .qty-input {
            width: 4.5rem;
            font: inherit;
            font-size: 0.9375rem;
            text-align: center;
            border: 1px solid var(--line);
            border-radius: var(--radius-btn);
            padding: 0 0.5rem;
        }
        .add-to-cart-row .btn-add-to-cart { flex: 1; }

        .cart-feedback { font-size: 0.875rem; margin-bottom: 0.75rem; padding: 0.5rem 0.875rem; border-radius: var(--radius-btn); }
        .cart-feedback--success { background: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); }
        .cart-feedback--error { background: #fee2e2; color: #991b1b; }

        /* --- Header shop group (Shop link + cart, always reachable when ecommerce is on) --- */
        .header-shop {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }
        .header-shop-link {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            font-size: 0.9375rem;
            font-weight: 500;
            color: var(--muted);
            text-decoration: none;
        }
        .header-shop-link:hover { color: var(--ink); text-decoration: none; }
        .header-shop-link.active { color: var(--accent); }

        /* --- Mini cart (header) --- */
        .mini-cart {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-family: 'Space Grotesk', system-ui, sans-serif;
            font-size: 0.9375rem;
            font-weight: 500;
            color: var(--ink);
            text-decoration: none;
        }
        .mini-cart:hover { color: var(--accent); text-decoration: none; }
        .mini-cart-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.25rem;
            height: 1.25rem;
            padding: 0 0.35rem;
            border-radius: var(--radius-pill);
            background: var(--accent);
            color: #fff;
            font-size: 0.6875rem;
            font-weight: 600;
        }

        /* --- Cart page --- */
        .cart-table { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem; }
        .cart-row {
            display: grid;
            grid-template-columns: 1fr auto auto auto;
            gap: 1.5rem;
            align-items: center;
            padding: 1.25rem;
            background: var(--soft);
            border-radius: var(--radius-card);
        }
        @media (max-width: 640px) {
            .cart-row { grid-template-columns: 1fr; gap: 0.75rem; }
        }
        .cart-row-name { font-weight: 600; font-family: 'Space Grotesk', sans-serif; margin-bottom: 0.125rem; }
        .cart-row-variant { font-size: 0.8125rem; color: var(--muted); margin-bottom: 0.25rem; }
        .cart-row-price { font-size: 0.875rem; color: var(--muted); }
        .cart-row-total { font-weight: 600; font-family: 'Space Grotesk', sans-serif; }
        .cart-remove-btn {
            background: none;
            border: none;
            color: #b91c1c;
            font-size: 0.8125rem;
            cursor: pointer;
            padding: 0;
        }
        .cart-remove-btn:hover { text-decoration: underline; }
        .cart-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-top: 2px solid var(--line);
            font-family: 'Space Grotesk', sans-serif;
        }
        .cart-summary-label { font-size: 1rem; color: var(--muted); }
        .cart-summary-total { font-size: 1.375rem; font-weight: 700; color: var(--accent); }
        .cart-checkout-link { display: flex; justify-content: flex-end; margin-top: 1.5rem; }
        .btn-link { text-align: center; text-decoration: none; }
        .btn-link:hover { text-decoration: none; }

        /* --- Checkout --- */
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 3rem;
            padding: 3rem 0 5rem;
            align-items: start;
        }
        @media (max-width: 768px) {
            .checkout-grid { grid-template-columns: 1fr; gap: 2rem; padding: 2rem 0 3rem; }
        }
        .checkout-heading { font-size: 1.125rem; margin: 2rem 0 1rem; }
        .checkout-heading:first-child { margin-top: 0; }
        .checkout-field { margin-bottom: 1.125rem; }
        .checkout-field label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--ink);
            margin-bottom: 0.375rem;
        }
        .checkout-field .optional { font-weight: 400; color: var(--muted); }
        .checkout-field input,
        .checkout-field textarea {
            width: 100%;
            font: inherit;
            font-size: 0.9375rem;
            color: var(--ink);
            background: var(--soft);
            border: 1px solid var(--line);
            border-radius: var(--radius-btn);
            padding: 0.625rem 0.75rem;
        }
        .checkout-field input:focus,
        .checkout-field textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 1px var(--accent);
        }
        .checkout-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .field-error { margin-top: 0.375rem; font-size: 0.8125rem; color: #b91c1c; }

        .checkout-summary {
            background: var(--soft);
            border-radius: var(--radius-card);
            padding: 1.75rem;
        }
        .checkout-summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.9375rem;
            color: var(--muted);
            margin-bottom: 0.75rem;
        }
        .checkout-summary-row--total {
            font-weight: 700;
            color: var(--ink);
            font-size: 1.125rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--line);
        }
        .checkout-note { font-size: 0.8125rem; color: var(--muted); margin: 1rem 0 1.25rem; }
        .checkout-summary .btn-add-to-cart { width: 100%; }

        .order-confirmation-address { margin-top: 2.5rem; }
        .order-confirmation-address p { color: var(--muted); }
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
