{{-- Default theme base layout. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Core meta --}}
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

    @php($accent = $themeSettings['primary_color'] ?? '#16513F')
    <style>
        :root { --accent: {{ $accent }}; }
        body { font-family: system-ui, sans-serif; color: #101413; margin: 0; }
        .wrap { max-width: 720px; margin: 0 auto; padding: 2rem 1.25rem; }
        a { color: var(--accent); }
        h1 { font-weight: 700; }
    </style>
</head>
<body>
    <main class="wrap">
        @yield('content')
    </main>
</body>
</html>
