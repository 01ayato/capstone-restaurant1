<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-dvh overflow-hidden">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">

        <title>@yield('title', 'Kitchen Display')</title>

        <script>
            (function () {
                try {
                    var stored = localStorage.getItem('sgTheme') || localStorage.getItem('portalTheme');
                    var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    var theme = stored === 'light' || stored === 'dark' ? stored : (prefersDark ? 'dark' : 'light');
                    document.documentElement.classList.toggle('dark', theme === 'dark');
                } catch (e) {}
            })();
        </script>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        @livewireStyles
        @stack('styles')
    </head>
    <body class="admin-shell h-dvh overflow-hidden font-sans antialiased">
        <main id="main-content" class="flex h-dvh w-full flex-col overflow-y-auto overscroll-y-contain">
            @hasSection('page-header')
                <header class="shrink-0 border-b border-gray-200 bg-white px-4 py-5 sm:px-6 lg:px-8">
                    @yield('page-header')
                </header>
            @endif

            <div class="flex w-full flex-1 flex-col px-4 py-5 sm:px-6 lg:px-8 lg:py-6">
                @yield('content')
            </div>
        </main>

        @livewireScripts
        @stack('scripts')
    </body>
</html>
