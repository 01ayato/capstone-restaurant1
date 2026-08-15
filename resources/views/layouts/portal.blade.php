<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-dvh">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $settings->description ?? "Smoke n' Grill — Premium grilled dishes and unforgettable dining experiences." }}">
    <meta name="color-scheme" content="light dark">

    <title>@yield('title', $settings->name ?? "Smoke n' Grill")</title>

    <script>
        (function () {
            try {
                var stored = localStorage.getItem('sgTheme') || localStorage.getItem('portalTheme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                var theme = stored === 'light' || stored === 'dark' ? stored : (prefersDark ? 'dark' : 'light');
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=playfair-display:600,700|inter:400,500,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @stack('styles')
</head>
<body class="portal-shell min-h-dvh font-sans antialiased">
    @php
        $flash = [];
        foreach (['success', 'error', 'warning', 'info'] as $key) {
            if (session()->has($key)) {
                $flash[$key] = session($key);
            }
        }
    @endphp
    @if ($flash !== [])
        <script type="application/json" id="flash-data">@json($flash)</script>
    @endif

    <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -left-32 top-16 h-96 w-96 rounded-full blur-3xl" style="background: var(--portal-glow-a);"></div>
        <div class="absolute -right-32 bottom-0 h-96 w-96 rounded-full blur-3xl" style="background: var(--portal-glow-b);"></div>
    </div>

    @include('portal.partials.navbar')

    <main class="relative z-10">
        @yield('content')
    </main>

    @include('portal.partials.footer')

    @stack('scripts')
</body>
</html>
