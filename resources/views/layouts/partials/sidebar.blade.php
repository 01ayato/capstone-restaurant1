{{-- Backdrop: mobile only --}}
<div
    x-show="sidebarOpen"
    x-transition
    class="fixed inset-0 z-40 bg-[#0D2232]/55 backdrop-blur-[2px] lg:hidden"
    aria-hidden="true"
    x-on:click="sidebarOpen = false"
></div>

<aside
    id="app-sidebar"
    class="admin-chrome fixed inset-y-0 left-0 z-50 flex h-dvh w-64 shrink-0 flex-col overflow-hidden border-r shadow-xl transition-[width,transform] duration-200 ease-out lg:static lg:z-0 lg:h-full lg:translate-x-0 lg:shadow-none"
    :class="[
        sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        sidebarCollapsed ? 'lg:w-0 lg:overflow-hidden lg:border-r-0' : 'lg:w-64',
    ]"
    aria-label="{{ __('Primary navigation') }}"
>
    <div class="flex h-16 shrink-0 items-center border-b px-5 lg:hidden" style="border-color: var(--admin-chrome-border)">
        <span class="admin-nav-section text-xs font-semibold uppercase tracking-[0.18em]">{{ __('Menu') }}</span>
    </div>

    <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto overscroll-y-contain px-3 py-5">
        @php
            $user = Auth::user();
            $isCashier = $user?->isCashier() ?? false;
            // superadmin + admin see Management & User Settings; cashier only sees Dashboard + Front of House.
            $canSeeManagement = $user?->isManager() ?? false;
            $canSeeFrontOfHouse = $canSeeManagement || $isCashier;

            $smokeNGrillAdminRoutes = [
                'menu_items' => 'admin.menu-items.*',
                'orders' => 'admin.orders.*',
                'customers' => 'admin.customers.*',
                'reservations' => 'admin.reservations.*',
                'sales_reports' => 'admin.reports.*',
                'restaurant_info' => 'admin.settings.*',
            ];

            $smokeModule = $sidebarModules->firstWhere('alias', 'smoke_n_grill');
            $userSettingsModule = $sidebarModules->firstWhere('alias', 'user_settings');
            $otherModules = $sidebarModules->reject(
                fn ($mod) => in_array($mod->alias, ['smoke_n_grill', 'user_settings'], true)
            );

            $frontOfHouseAliases = ['pos', 'orders', 'reservations', 'customers', 'view_website'];
            $managementAliases = ['menu_items', 'sales_reports', 'restaurant_info', 'tables_configurations'];

            $routablePages = $smokeModule
                ? $smokeModule->pages->filter(fn ($pg) => Route::has('page.smoke_n_grill.'.$pg->page_alias.'.index'))->values()
                : collect();
            $frontOfHousePages = $routablePages->whereIn('page_alias', $frontOfHouseAliases)->values();
            $managementPages = $routablePages->whereIn('page_alias', $managementAliases)->values();

            $pageIsActive = function ($page) use ($smokeNGrillAdminRoutes) {
                $active = request()->routeIs('page.smoke_n_grill.'.$page->page_alias.'.*');
                if (isset($smokeNGrillAdminRoutes[$page->page_alias])) {
                    $active = $active || request()->routeIs($smokeNGrillAdminRoutes[$page->page_alias]);
                }

                return $active;
            };

            $dashboardActive = request()->routeIs('dashboard');
            $frontOfHouseActive = $dashboardActive || $frontOfHousePages->contains(fn ($pg) => $pageIsActive($pg));
            $managementActive = $managementPages->contains(fn ($pg) => $pageIsActive($pg));
        @endphp

        @if ($smokeModule)
            <p class="admin-nav-section px-3 pb-1 pt-5 text-[10px] font-bold uppercase tracking-[0.18em]">{{ __('Modules') }}</p>
            <div class="space-y-1">
                {{-- Front of House (cashier tasks) -- visible to ALL roles --}}
                @if ($frontOfHousePages->isNotEmpty())
                    <div class="rounded-lg" x-data="{ open: {{ $frontOfHouseActive ? 'true' : 'false' }} }">
                        <button
                            type="button"
                            class="flex w-full items-center gap-2 rounded-[10px] px-2 py-2 text-sm font-medium"
                            style="color: {{ $frontOfHouseActive ? 'var(--admin-chrome-text)' : 'var(--admin-chrome-muted)' }}; background: {{ $frontOfHouseActive ? 'color-mix(in srgb, var(--admin-chrome-text) 6%, transparent)' : 'transparent' }}"
                            x-bind:aria-expanded="open"
                            x-on:click="open = ! open"
                        >
                            <svg
                                class="size-4 shrink-0 transition-transform"
                                x-bind:class="open ? 'rotate-90' : ''"
                                style="color: var(--admin-chrome-section)"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                                aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                            @include('layouts.partials.sidebar-folder-toggle-icon', ['initialOpen' => $frontOfHouseActive])
                            <span class="min-w-0 flex-1 truncate text-left">{{ __('Front of House') }}</span>
                        </button>

                        <div
                            x-show="open"
                            x-transition
                            class="ml-4 mt-1 space-y-1 border-l pl-2"
                            style="border-color: var(--admin-chrome-border)"
                            role="group"
                            aria-label="{{ __('Front of House') }}"
                        >
                            <a
                                href="{{ route('dashboard') }}"
                                x-on:click="sidebarOpen = false"
                                @if ($dashboardActive) data-sidebar-active @endif
                                class="{{ $dashboardActive ? 'admin-nav-item admin-nav-item--active' : 'admin-nav-item' }} !min-h-10 !py-2 !pl-3 !pr-2"
                            >
                                <x-admin-icon name="layout-dashboard" size="size-4" />
                                <span class="min-w-0 truncate">{{ __('Dashboard') }}</span>
                            </a>
                            @foreach ($frontOfHousePages as $pg)
                                @php $active = $pageIsActive($pg); @endphp
                                <a
                                    href="{{ route('page.smoke_n_grill.'.$pg->page_alias.'.index') }}"
                                    x-on:click="sidebarOpen = false"
                                    @if ($active) data-sidebar-active @endif
                                    class="{{ $active ? 'admin-nav-item admin-nav-item--active' : 'admin-nav-item' }} !min-h-10 !py-2 !pl-3 !pr-2"
                                >
                                    @include('layouts.partials.sidebar-page-icon', ['icon' => $pg->icon])
                                    <span class="min-w-0 truncate">{{ $pg->page_name }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Management (admin tasks) --}}
                @if ($canSeeManagement && $managementPages->isNotEmpty())
                    <div class="rounded-lg" x-data="{ open: {{ $managementActive ? 'true' : 'false' }} }">
                        <button
                            type="button"
                            class="flex w-full items-center gap-2 rounded-[10px] px-2 py-2 text-sm font-medium"
                            style="color: {{ $managementActive ? 'var(--admin-chrome-text)' : 'var(--admin-chrome-muted)' }}; background: {{ $managementActive ? 'color-mix(in srgb, var(--admin-chrome-text) 6%, transparent)' : 'transparent' }}"
                            x-bind:aria-expanded="open"
                            x-on:click="open = ! open"
                        >
                            <svg
                                class="size-4 shrink-0 transition-transform"
                                x-bind:class="open ? 'rotate-90' : ''"
                                style="color: var(--admin-chrome-section)"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                                aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                            @include('layouts.partials.sidebar-folder-toggle-icon', ['initialOpen' => $managementActive])
                            <span class="min-w-0 flex-1 truncate text-left">{{ __('Management') }}</span>
                        </button>

                        <div
                            x-show="open"
                            x-transition
                            class="ml-4 mt-1 space-y-1 border-l pl-2"
                            style="border-color: var(--admin-chrome-border)"
                            role="group"
                            aria-label="{{ __('Management') }}"
                        >
                            @foreach ($managementPages as $pg)
                                @php $active = $pageIsActive($pg); @endphp
                                <a
                                    href="{{ route('page.smoke_n_grill.'.$pg->page_alias.'.index') }}"
                                    x-on:click="sidebarOpen = false"
                                    @if ($active) data-sidebar-active @endif
                                    class="{{ $active ? 'admin-nav-item admin-nav-item--active' : 'admin-nav-item' }} !min-h-10 !py-2 !pl-3 !pr-2"
                                >
                                    @include('layouts.partials.sidebar-page-icon', ['icon' => $pg->icon])
                                    <span class="min-w-0 truncate">{{ $pg->page_name }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Any other modules (fallback) --}}
        @foreach ($otherModules as $mod)
            @php
                $hasModuleRoute = Route::has('module.'.$mod->alias.'.index');
                $routable = $mod->pages->filter(fn ($pg) => Route::has('page.'.$mod->alias.'.'.$pg->page_alias.'.index'))->values();
                $moduleActive = request()->routeIs('module.'.$mod->alias.'.*')
                    || $routable->contains(fn ($pg) => request()->routeIs('page.'.$mod->alias.'.'.$pg->page_alias.'.*'));
            @endphp
            @continue(! $hasModuleRoute && $routable->isEmpty())
            @if ($hasModuleRoute)
                <a
                    href="{{ route('module.'.$mod->alias.'.index') }}"
                    x-on:click="sidebarOpen = false"
                    class="{{ $moduleActive ? 'admin-nav-item admin-nav-item--active' : 'admin-nav-item' }}"
                >
                    @include('layouts.partials.sidebar-icon', ['icon' => 'document'])
                    <span class="min-w-0 truncate">{{ $mod->name }}</span>
                </a>
            @endif
        @endforeach

        {{-- User Settings (admin only) --}}
        @if ($canSeeManagement && $userSettingsModule)
            @php
                $usPages = $userSettingsModule->pages->filter(
                    fn ($pg) => Route::has('page.user_settings.'.$pg->page_alias.'.index')
                )->values();
                $usActive = $usPages->contains(fn ($pg) => request()->routeIs('page.user_settings.'.$pg->page_alias.'.*'))
                    || request()->routeIs('module.user_settings.*');
            @endphp
            @if ($usPages->isNotEmpty())
                <p class="admin-nav-section px-3 pb-1 pt-5 text-[10px] font-bold uppercase tracking-[0.18em]">{{ __('Settings') }}</p>
                <div class="rounded-lg" x-data="{ open: {{ $usActive ? 'true' : 'false' }} }">
                    <button
                        type="button"
                        class="flex w-full items-center gap-2 rounded-[10px] px-2 py-2 text-sm font-medium"
                        style="color: {{ $usActive ? 'var(--admin-chrome-text)' : 'var(--admin-chrome-muted)' }}; background: {{ $usActive ? 'color-mix(in srgb, var(--admin-chrome-text) 6%, transparent)' : 'transparent' }}"
                        x-bind:aria-expanded="open"
                        x-on:click="open = ! open"
                    >
                        <svg
                            class="size-4 shrink-0 transition-transform"
                            x-bind:class="open ? 'rotate-90' : ''"
                            style="color: var(--admin-chrome-section)"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                        @include('layouts.partials.sidebar-folder-toggle-icon', ['initialOpen' => $usActive])
                        <span class="min-w-0 flex-1 truncate text-left">{{ $userSettingsModule->name }}</span>
                    </button>

                    <div
                        x-show="open"
                        x-transition
                        class="ml-4 mt-1 space-y-1 border-l pl-2"
                        style="border-color: var(--admin-chrome-border)"
                        role="group"
                        aria-label="{{ $userSettingsModule->name }}"
                    >
                        @foreach ($usPages as $pg)
                            @php $active = request()->routeIs('page.user_settings.'.$pg->page_alias.'.*'); @endphp
                            <a
                                href="{{ route('page.user_settings.'.$pg->page_alias.'.index') }}"
                                x-on:click="sidebarOpen = false"
                                @if ($active) data-sidebar-active @endif
                                class="{{ $active ? 'admin-nav-item admin-nav-item--active' : 'admin-nav-item' }} !min-h-10 !py-2 !pl-3 !pr-2"
                            >
                                @include('layouts.partials.sidebar-page-icon', ['icon' => $pg->icon])
                                <span class="min-w-0 truncate">{{ $pg->page_name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </nav>

    <div class="hidden shrink-0 border-t p-3 lg:block" style="border-color: var(--admin-chrome-border)" x-show="!sidebarCollapsed" x-cloak>
        <button
            type="button"
            x-on:click="toggleSidebarCollapsed()"
            class="admin-nav-item w-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-400"
        >
            <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 19.5l-7.5-7.5 7.5-7.5m-6 15L5.25 12l7.5-7.5" />
            </svg>
            {{ __('Collapse sidebar') }}
        </button>
    </div>
</aside>
