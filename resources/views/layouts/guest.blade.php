<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sign in' }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-soft text-ink antialiased font-sans">
    <div class="min-h-screen flex flex-col items-center justify-center px-4">
        <div class="w-full max-w-sm">
            <div class="mb-8 text-center">
                <a href="{{ url('/') }}" class="font-display text-2xl font-bold tracking-tight text-ink">
                    Seconds<span class="text-accent">.</span>
                </a>
                <p class="mt-1 text-sm text-muted">Admin</p>
            </div>

            <div class="rounded-[var(--radius-panel)] border border-line bg-bg p-7 shadow-sm">
                {{ $slot }}
            </div>

            <p class="mt-6 text-center text-xs text-muted">
                &copy; {{ date('Y') }} {{ config('app.name') }}
            </p>
        </div>
    </div>
</body>
</html>
