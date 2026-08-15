<nav class="sticky top-0 z-50 border-b border-[#E8E3DB] bg-[#FAF8F5]/90 text-[#0D2232] shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-[#0D2232]/95 dark:text-white">
    <div class="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
        <a href="{{ route('landing') }}" class="group flex min-w-0 items-center gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-orange-500 text-lg font-bold text-white shadow-sm transition group-hover:bg-orange-400">
                <i class="fa-solid fa-fire-flame-curved" aria-hidden="true"></i>
            </span>
            <span class="truncate text-base font-bold tracking-tight text-[#0D2232] sm:text-lg dark:text-white">
                {{ $settings->name ?? "Smoke n' Grill" }}
            </span>
        </a>

        <div class="hidden items-center gap-1 md:flex">
            @auth
                @if (auth()->user()->isStaff())
                    <a href="{{ route('dashboard') }}" class="portal-nav-link {{ request()->routeIs('dashboard') || request()->routeIs('admin.*') ? 'portal-nav-link--active' : '' }}">
                        <i class="fa-solid fa-gauge-high mr-1.5 text-xs" aria-hidden="true"></i> Dashboard
                    </a>
                @else
                    <a href="{{ route('landing') }}" class="portal-nav-link {{ request()->routeIs('landing') ? 'portal-nav-link--active' : '' }}">Home</a>
                    <a href="{{ route('menu.index') }}" class="portal-nav-link {{ request()->routeIs('menu.*') ? 'portal-nav-link--active' : '' }}">Menu</a>
                    <a href="{{ route('cart.index') }}" class="portal-nav-link {{ request()->routeIs('cart.*') ? 'portal-nav-link--active' : '' }}">Order</a>
                    <a href="{{ route('orders.index') }}" class="portal-nav-link {{ request()->routeIs('orders.*') ? 'portal-nav-link--active' : '' }}">My Orders</a>
                    <a href="{{ route('reservations.index') }}" class="portal-nav-link {{ request()->routeIs('reservations.index') ? 'portal-nav-link--active' : '' }}">Reserve</a>
                    <a href="{{ route('reservations.mine') }}" class="portal-nav-link {{ request()->routeIs('reservations.mine') ? 'portal-nav-link--active' : '' }}">My Reservation</a>
                    <a href="{{ route('profile.show') }}" class="portal-nav-link {{ request()->routeIs('profile.*') ? 'portal-nav-link--active' : '' }}">Profile</a>
                @endif
            @else
                <a href="{{ route('landing') }}" class="portal-nav-link {{ request()->routeIs('landing') ? 'portal-nav-link--active' : '' }}">Home</a>
                <a href="{{ route('menu.index') }}" class="portal-nav-link {{ request()->routeIs('menu.*') ? 'portal-nav-link--active' : '' }}">Menu</a>
            @endauth
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
            @if (!empty($currentTable))
                <span class="hidden items-center gap-1.5 rounded-[10px] border border-emerald-300/60 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 lg:inline-flex dark:border-emerald-400/30 dark:bg-emerald-400/10 dark:text-emerald-300" title="Your table">
                    <i class="fa-solid fa-chair" aria-hidden="true"></i>
                    {{ $currentTable['location_label'] }}
                </span>
            @endif

            <button
                type="button"
                class="inline-flex size-10 items-center justify-center rounded-[10px] border border-[#DDD7CE] bg-white text-[#0D2232] transition hover:bg-[#F3F0EB] hover:text-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-400 dark:border-white/15 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 dark:hover:text-orange-300"
                x-data="{
                    dark: document.documentElement.classList.contains('dark'),
                    async toggle(event) {
                        const nextDark = !this.dark;
                        if (typeof window.togglePortalThemeWithWave === 'function') {
                            await window.togglePortalThemeWithWave(event, nextDark);
                        } else {
                            document.documentElement.classList.toggle('dark', nextDark);
                            try {
                                localStorage.setItem('sgTheme', nextDark ? 'dark' : 'light');
                                localStorage.setItem('portalTheme', nextDark ? 'dark' : 'light');
                            } catch (e) {}
                        }
                        this.dark = nextDark;
                    }
                }"
                x-on:click="toggle($event)"
                :aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'"
                :title="dark ? 'Light mode' : 'Dark mode'"
            >
                <i class="fa-solid fa-moon text-sm" x-show="!dark" aria-hidden="true"></i>
                <i class="fa-solid fa-sun text-sm text-orange-500 dark:text-orange-300" x-show="dark" x-cloak aria-hidden="true"></i>
            </button>

            <a href="{{ route('cart.index') }}" class="relative flex size-10 items-center justify-center rounded-[10px] border border-[#DDD7CE] bg-white text-[#0D2232] transition hover:bg-[#F3F0EB] hover:text-orange-600 dark:border-white/15 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 dark:hover:text-orange-300" aria-label="Cart">
                <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
                @if (($cartCount ?? 0) > 0)
                    <span class="absolute -right-1 -top-1 flex size-5 items-center justify-center rounded-full bg-orange-500 text-[10px] font-bold text-white">{{ $cartCount }}</span>
                @endif
            </a>

            @auth
                <form action="{{ route('logout') }}" method="post" class="hidden sm:block">
                    @csrf
                    <button type="submit" class="rounded-[10px] border border-[#DDD7CE] bg-white px-4 py-2 text-sm font-medium text-[#0D2232] transition hover:bg-[#F3F0EB] dark:border-white/15 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">
                        Sign out
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="hidden rounded-[10px] border border-[#DDD7CE] bg-white px-4 py-2 text-sm font-medium text-[#0D2232] transition hover:bg-[#F3F0EB] sm:inline-block dark:border-white/15 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">
                    Sign in
                </a>
                <a href="{{ route('register') }}" class="rounded-[10px] bg-orange-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-400">
                    Sign up
                </a>
            @endauth

            <button type="button" class="flex size-10 items-center justify-center rounded-[10px] border border-[#DDD7CE] bg-white text-[#0D2232] hover:bg-[#F3F0EB] md:hidden dark:border-white/15 dark:bg-white/5 dark:text-white dark:hover:bg-white/10" x-data x-on:click="$dispatch('toggle-mobile-nav')" aria-label="Toggle menu">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <div class="border-t border-[#E8E3DB] md:hidden dark:border-white/10" x-data="{ open: false }" x-on:toggle-mobile-nav.window="open = !open">
        <div x-show="open" x-cloak class="space-y-1 px-4 py-3">
            @auth
                @if (auth()->user()->isStaff())
                    <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2 text-sm text-[#0D2232]/75 hover:bg-[#F0EBE4] hover:text-[#0D2232] dark:text-white/80 dark:hover:bg-white/10 dark:hover:text-white">Dashboard</a>
                @else
                    <a href="{{ route('landing') }}" class="block rounded-lg px-3 py-2 text-sm text-[#0D2232]/75 hover:bg-[#F0EBE4] hover:text-[#0D2232] dark:text-white/80 dark:hover:bg-white/10 dark:hover:text-white">Home</a>
                    <a href="{{ route('menu.index') }}" class="block rounded-lg px-3 py-2 text-sm text-[#0D2232]/75 hover:bg-[#F0EBE4] hover:text-[#0D2232] dark:text-white/80 dark:hover:bg-white/10 dark:hover:text-white">Menu</a>
                    <a href="{{ route('cart.index') }}" class="block rounded-lg px-3 py-2 text-sm text-[#0D2232]/75 hover:bg-[#F0EBE4] hover:text-[#0D2232] dark:text-white/80 dark:hover:bg-white/10 dark:hover:text-white">Order</a>
                    <a href="{{ route('orders.index') }}" class="block rounded-lg px-3 py-2 text-sm text-[#0D2232]/75 hover:bg-[#F0EBE4] hover:text-[#0D2232] dark:text-white/80 dark:hover:bg-white/10 dark:hover:text-white">My Orders</a>
                    <a href="{{ route('reservations.index') }}" class="block rounded-lg px-3 py-2 text-sm text-[#0D2232]/75 hover:bg-[#F0EBE4] hover:text-[#0D2232] dark:text-white/80 dark:hover:bg-white/10 dark:hover:text-white">Reserve</a>
                    <a href="{{ route('reservations.mine') }}" class="block rounded-lg px-3 py-2 text-sm text-[#0D2232]/75 hover:bg-[#F0EBE4] hover:text-[#0D2232] dark:text-white/80 dark:hover:bg-white/10 dark:hover:text-white">My Reservation</a>
                    <a href="{{ route('profile.show') }}" class="block rounded-lg px-3 py-2 text-sm text-[#0D2232]/75 hover:bg-[#F0EBE4] hover:text-[#0D2232] dark:text-white/80 dark:hover:bg-white/10 dark:hover:text-white">Profile</a>
                @endif
            @else
                <a href="{{ route('landing') }}" class="block rounded-lg px-3 py-2 text-sm text-[#0D2232]/75 hover:bg-[#F0EBE4] hover:text-[#0D2232] dark:text-white/80 dark:hover:bg-white/10 dark:hover:text-white">Home</a>
                <a href="{{ route('menu.index') }}" class="block rounded-lg px-3 py-2 text-sm text-[#0D2232]/75 hover:bg-[#F0EBE4] hover:text-[#0D2232] dark:text-white/80 dark:hover:bg-white/10 dark:hover:text-white">Menu</a>
                <a href="{{ route('login') }}" class="block rounded-lg px-3 py-2 text-sm text-[#0D2232]/75 hover:bg-[#F0EBE4] hover:text-[#0D2232] dark:text-white/80 dark:hover:bg-white/10 dark:hover:text-white">Sign in</a>
            @endauth
        </div>
    </div>
</nav>
