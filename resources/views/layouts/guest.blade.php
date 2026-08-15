<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-dvh bg-zinc-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Laravel'))</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        @livewireStyles
    </head>
    <body class="min-h-dvh bg-zinc-50 font-sans text-zinc-900 antialiased">
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

        @yield('content')

        @livewireScripts
    </body>
</html>
