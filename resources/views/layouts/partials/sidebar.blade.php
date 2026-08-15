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
            $smokeNGrillAdminRoutes = [
                'menu_items' => 'admin.menu-items.*',
                'orders' => 'admin.orders.*',
                'customers' => 'admin.customers.*',
                'reservations' => 'admin.reservations.*',
                'sales_reports' => 'admin.reports.*',
                'restaurant_info' => 'admin.settings.*',
            ];
        @endphp
        <a
            href="{{ route('dashboard') }}"
            x-on:click="sidebarOpen = false"
            class="{{ request()->routeIs('dashboard') ? 'admin-nav-item admin-nav-item--active' : 'admin-nav-item' }}"
        >
            <x-admin-icon name="layout-dashboard" />
            {{ __('Dashboard') }}
        </a>

        @if ($sidebarModules->isNotEmpty())
            <p class="admin-nav-section px-3 pb-1 pt-5 text-[10px] font-bold uppercase tracking-[0.18em]">{{ __('Modules') }}</p>
            <div class="space-y-1">
                @foreach ($sidebarModules as $mod)
                    @php
                        $hasModuleRoute = Route::has('module.'.$mod->alias.'.index');
                        $routablePages = $mod->pages->filter(
                            fn ($pg) => Route::has('page.'.$mod->alias.'.'.$pg->page_alias.'.index')
                        );
                        $moduleActive =
                            request()->routeIs('module.'.$mod->alias.'.*')
                            || $routablePages->contains(function ($pg) use ($mod, $smokeNGrillAdminRoutes) {
                                if (request()->routeIs('page.'.$mod->alias.'.'.$pg->page_alias.'.*')) {
                                    return true;
                                }
                                if ($mod->alias === 'smoke_n_grill' && isset($smokeNGrillAdminRoutes[$pg->page_alias])) {
                                    return request()->routeIs($smokeNGrillAdminRoutes[$pg->page_alias]);
                                }

                                return false;
                            });
                    @endphp
                    @continue(! $hasModuleRoute && $routablePages->isEmpty())
                    @php
                        $useTree = $mod->sidebar_expand_children && $routablePages->isNotEmpty();
                    @endphp

                    @if ($useTree)
                        <div class="rounded-lg">
                            <div
                                class="flex w-full items-center gap-2 rounded-[10px] px-2 py-2 text-sm font-medium"
                                style="color: {{ $moduleActive ? 'var(--admin-chrome-text)' : 'var(--admin-chrome-muted)' }}; background: {{ $moduleActive ? 'color-mix(in srgb, var(--admin-chrome-text) 6%, transparent)' : 'transparent' }}"
                                aria-expanded="true"
                            >
                                <svg
                                    class="size-4 shrink-0 rotate-90"
                                    style="color: var(--admin-chrome-section)"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                                @include('layouts.partials.sidebar-folder-toggle-icon', ['initialOpen' => true])
                                <span class="min-w-0 flex-1 truncate">{{ $mod->name }}</span>
                            </div>

                            <div
                                class="ml-4 mt-1 space-y-1 border-l pl-2"
                                style="border-color: var(--admin-chrome-border)"
                                role="group"
                                aria-label="{{ $mod->name }}"
                            >
                                @foreach ($routablePages as $pg)
                                    @php
                                        $pageIsActive = request()->routeIs('page.'.$mod->alias.'.'.$pg->page_alias.'.*');
                                        if ($mod->alias === 'smoke_n_grill' && isset($smokeNGrillAdminRoutes[$pg->page_alias])) {
                                            $pageIsActive = $pageIsActive || request()->routeIs($smokeNGrillAdminRoutes[$pg->page_alias]);
                                        }
                                    @endphp
                                    <a
                                        href="{{ route('page.'.$mod->alias.'.'.$pg->page_alias.'.index') }}"
                                        x-on:click="sidebarOpen = false"
                                        @if ($pageIsActive) data-sidebar-active @endif
                                        class="{{ $pageIsActive ? 'admin-nav-item admin-nav-item--active' : 'admin-nav-item' }} !min-h-10 !py-2 !pl-3 !pr-2"
                                    >
                                        @include('layouts.partials.sidebar-page-icon', ['icon' => $pg->icon])
                                        <span class="min-w-0 truncate">{{ $pg->page_name }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @elseif ($hasModuleRoute)
                        <a
                            href="{{ route('module.'.$mod->alias.'.index') }}"
                            x-on:click="sidebarOpen = false"
                            class="{{ $moduleActive ? 'admin-nav-item admin-nav-item--active' : 'admin-nav-item' }}"
                        >
                            @include('layouts.partials.sidebar-icon', ['icon' => 'document'])
                            <span class="min-w-0 truncate">{{ $mod->name }}</span>
                        </a>
                    @else
                        @foreach ($routablePages as $pg)
                            @php
                                $pageIsActive = request()->routeIs('page.'.$mod->alias.'.'.$pg->page_alias.'.*');
                                if ($mod->alias === 'smoke_n_grill' && isset($smokeNGrillAdminRoutes[$pg->page_alias])) {
                                    $pageIsActive = $pageIsActive || request()->routeIs($smokeNGrillAdminRoutes[$pg->page_alias]);
                                }
                            @endphp
                            <a
                                href="{{ route('page.'.$mod->alias.'.'.$pg->page_alias.'.index') }}"
                                x-on:click="sidebarOpen = false"
                                @if ($pageIsActive) data-sidebar-active @endif
                                class="{{ $pageIsActive ? 'admin-nav-item admin-nav-item--active' : 'admin-nav-item' }} !ml-3 !min-h-10 !gap-2 !py-1.5 !pl-3 !pr-2 !text-xs"
                            >
                                @include('layouts.partials.sidebar-page-icon', ['icon' => $pg->icon])
                                <span class="min-w-0 truncate">{{ $pg->page_name }}</span>
                            </a>
                        @endforeach
                    @endif
                @endforeach
            </div>
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
