<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-dvh overflow-hidden">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">

        <title>@yield('title', config('app.name', 'Laravel'))</title>

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
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        @livewireStyles
        @stack('styles')
    </head>
    <body class="admin-shell h-dvh overflow-hidden font-sans antialiased">
        @php
            $flash = [];
            foreach (['success', 'error', 'warning', 'info', 'print_slip_url'] as $key) {
                if (session()->has($key)) {
                    $flash[$key] = session($key);
                }
            }
        @endphp
        @if ($flash !== [])
            <script type="application/json" id="flash-data">@json($flash)</script>
        @endif

        <div
            x-data="{
                sidebarOpen: false,
                sidebarCollapsed: (() => {
                    const stored = localStorage.getItem('sidebarCollapsed');
                    if (stored !== null) {
                        return stored === 'true';
                    }
                    return {{ request()->routeIs('page.smoke_n_grill.pos.*') ? 'true' : 'false' }};
                })(),
                toggleSidebarCollapsed() {
                    this.sidebarCollapsed = !this.sidebarCollapsed;
                    localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed ? 'true' : 'false');
                },
            }"
            x-on:keydown.window.escape="sidebarOpen = false"
            class="flex h-dvh flex-col overflow-hidden"
        >
            @include('layouts.partials.navbar')

            <div class="flex min-h-0 flex-1 overflow-hidden">
                @include('layouts.partials.sidebar')

                <main id="main-content" class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain">
                    @hasSection('page-header')
                        <div class="border-b border-gray-200 bg-white px-4 py-6 sm:px-6 lg:px-8">
                            @yield('page-header')
                        </div>
                    @endif

                    <div class="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>

        @livewireScripts
        @stack('scripts')
    </body>
</html>
