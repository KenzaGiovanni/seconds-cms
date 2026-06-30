{{-- Default theme base layout. Themes override this by shipping their own views/layout.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @isset($description)
        <meta name="description" content="{{ $description }}">
    @endisset
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
