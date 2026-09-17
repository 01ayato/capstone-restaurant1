<header
    class="admin-chrome sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 border-b px-4 shadow-sm lg:px-6"
    role="banner"
>
    <button
        type="button"
        class="admin-chrome-btn size-10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-400 lg:hidden"
        aria-controls="app-sidebar"
        :aria-expanded="sidebarOpen"
        x-on:click="sidebarOpen = !sidebarOpen"
    >
        <span class="sr-only">{{ __('Toggle sidebar') }}</span>
        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
    </button>

    <button
        type="button"
        class="admin-chrome-btn hidden size-10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-400 lg:inline-flex"
        aria-controls="app-sidebar"
        :aria-expanded="!sidebarCollapsed"
        x-on:click="toggleSidebarCollapsed()"
    >
        <span class="sr-only" x-text="sidebarCollapsed ? '{{ __('Expand sidebar') }}' : '{{ __('Collapse sidebar') }}'"></span>
        <svg x-show="!sidebarCollapsed" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 19.5l-7.5-7.5 7.5-7.5m-6 15L5.25 12l7.5-7.5" />
        </svg>
        <svg x-show="sidebarCollapsed" x-cloak class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
    </button>

    <div class="flex min-w-0 flex-1 items-center justify-between gap-3">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3" style="color: var(--admin-chrome-text)">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-[10px] bg-orange-500/10 text-orange-500 shadow-sm ring-1 ring-orange-500/20 dark:bg-white/10 dark:text-orange-400 dark:ring-white/10">
                <x-admin-icon name="store" size="size-[18px]" />
            </span>
            <span class="truncate text-sm font-bold tracking-tight sm:text-base">{{ config('app.name', 'Laravel') }}</span>
        </a>

        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
            <button
                type="button"
                class="admin-chrome-btn size-10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-400"
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

            @php
                $roleSlug = auth()->user()->role?->slug;
                $roleBadge = [
                    'admin' => ['label' => 'Admin', 'class' => 'bg-blue-100 text-blue-700 ring-blue-200 dark:bg-blue-500/15 dark:text-blue-300 dark:ring-blue-500/30'],
                    'cashier' => ['label' => 'Cashier', 'class' => 'bg-emerald-100 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30'],
                    'superadmin' => ['label' => 'Superadmin', 'class' => 'bg-violet-100 text-violet-700 ring-violet-200 dark:bg-violet-500/15 dark:text-violet-300 dark:ring-violet-500/30'],
                ][$roleSlug] ?? ['label' => ucfirst($roleSlug ?? 'User'), 'class' => 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-white/5 dark:text-slate-300 dark:ring-white/10'];
            @endphp
            <span
                class="hidden max-w-48 truncate rounded-full px-3 py-1.5 text-xs font-medium ring-1 sm:inline sm:max-w-xs"
                style="background: var(--admin-chrome-btn); color: var(--admin-chrome-muted); box-shadow: inset 0 0 0 1px var(--admin-chrome-border)"
            >
                {{ auth()->user()->email }}
            </span>
            <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 {{ $roleBadge['class'] }}">
                {{ $roleBadge['label'] }}
            </span>
            <form
                action="{{ route('logout') }}"
                method="post"
                class="shrink-0"
                data-confirm-title="{{ __('Sign out?') }}"
                data-confirm-text="{{ __('You will need to sign in again to access your account.') }}"
                data-confirm-yes="{{ __('Sign out') }}"
                data-confirm-no="{{ __('Cancel') }}"
                x-on:submit.prevent="(el => confirmDialog({ title: el.dataset.confirmTitle, text: el.dataset.confirmText, confirmButtonText: el.dataset.confirmYes, cancelButtonText: el.dataset.confirmNo, icon: 'question' }).then(ok => ok && el.submit()))($el)"
            >
                @csrf
                <button
                    type="submit"
                    class="admin-chrome-btn min-h-9 px-3 py-1.5 text-xs font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-400 sm:px-4 sm:text-sm"
                >
                    {{ __('Sign out') }}
                </button>
            </form>
        </div>
    </div>
</header>
