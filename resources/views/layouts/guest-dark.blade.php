<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-dvh bg-zinc-950">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', "Smoke n' Grill")</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=playfair-display:600,700|inter:400,500,600,700" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-dvh bg-zinc-950 font-sans text-zinc-100 antialiased">
        @php
            $flash = [];
            foreach (['success', 'error', 'warning', 'info'] as $key) {
                if (session()->has($key)) { $flash[$key] = session($key); }
            }
        @endphp
        @if ($flash !== [])
            <script type="application/json" id="flash-data">@json($flash)</script>
        @endif
        <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -left-32 top-0 h-96 w-96 rounded-full bg-orange-600/10 blur-3xl"></div>
            <div class="absolute -right-32 bottom-0 h-96 w-96 rounded-full bg-amber-500/10 blur-3xl"></div>
        </div>
        @yield('content')
    </body>
</html>
