@extends('layouts.app')

@section('title', '  — ' . config('app.name'))

@section('page-header')
    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-600">Smoke n' Grill</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Restaurant Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">A clear view of today’s restaurant operations.</p>
        </div>
        <p class="mt-3 text-xs font-medium text-gray-400 sm:mt-0">{{ now()->format('l, F j, Y') }}</p>
    </div>
@endsection

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="admin-card admin-card-hover flex items-start justify-between gap-4">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-gray-500">Total Orders</p>
                <p class="mt-3 text-3xl font-bold tracking-tight text-gray-900">{{ number_format($totalOrders) }}</p>
            </div>
            <span class="flex size-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                <x-admin-icon name="package" size="size-6" />
            </span>
        </div>
        <div class="admin-card admin-card-hover flex items-start justify-between gap-4">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-gray-500">Total Sales</p>
                <p class="mt-3 text-3xl font-bold tracking-tight text-gray-900">{{ \App\Support\Peso::format($totalSales) }}</p>
            </div>
            <span class="flex size-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-500">
                <x-admin-icon name="dollar-sign" size="size-6" />
            </span>
        </div>
        <div class="admin-card admin-card-hover flex items-start justify-between gap-4">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-gray-500">Customers</p>
                <p class="mt-3 text-3xl font-bold tracking-tight text-gray-900">{{ number_format($totalCustomers) }}</p>
            </div>
            <span class="flex size-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-500">
                <x-admin-icon name="users" size="size-6" />
            </span>
        </div>
        <div class="admin-card admin-card-hover flex items-start justify-between gap-4">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-gray-500">Revenue Today</p>
                <p class="mt-3 text-3xl font-bold tracking-tight text-gray-900">{{ \App\Support\Peso::format($revenueToday) }}</p>
            </div>
            <span class="flex size-12 items-center justify-center rounded-xl bg-orange-50 text-orange-500">
                <x-admin-icon name="trending-up" size="size-6" />
            </span>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="admin-card">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-400">At a glance</p>
                <h2 class="mt-1 text-lg font-bold text-gray-900">Quick Stats</h2>
            </div>
            <dl class="mt-5 divide-y divide-gray-100 text-sm">
                <div class="flex items-center justify-between py-3">
                    <dt class="font-medium text-gray-600">Pending Orders</dt>
                    <dd class="admin-badge-pending min-w-9 justify-center">{{ $pendingOrders }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="font-medium text-gray-600">Pending Reservations</dt>
                    <dd class="admin-badge-preparing min-w-9 justify-center">{{ $pendingReservations }}</dd>
                </div>
            </dl>
            <div class="mt-6 grid gap-3 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                <a href="{{ route('admin.orders.index') }}" class="admin-btn-primary justify-start">
                    <x-admin-icon name="package" size="size-4" />
                    Manage Orders
                </a>
                @if (auth()->user()?->isManager())
                    <a href="{{ route('admin.menu-items.index') }}" class="admin-btn-secondary justify-start">
                        <x-admin-icon name="utensils" size="size-4" />
                        Menu Items
                    </a>
                @endif
                <a href="{{ route('landing') }}" class="admin-btn-secondary justify-start">
                    <x-admin-icon name="external-link" size="size-4" />
                    View Website
                </a>
            </div>
        </div>

        <div class="admin-card">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-400">Latest activity</p>
                    <h2 class="mt-1 text-lg font-bold text-gray-900">Recent Orders</h2>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="text-xs font-semibold text-orange-600 transition hover:text-orange-500">View all</a>
            </div>
            @if ($recentOrders->isEmpty())
                <div class="mt-5 rounded-xl bg-slate-50 px-4 py-8 text-center">
                    <x-admin-icon name="package" size="mx-auto size-6 text-gray-400" />
                    <p class="mt-2 text-sm text-gray-500">No orders yet.</p>
                </div>
            @else
                <ul class="mt-4 divide-y divide-gray-100">
                    @foreach ($recentOrders as $order)
                        @php
                            $recentStatusClass = match (strtolower($order->statusLabel())) {
                                'completed' => 'admin-badge-completed',
                                'cancelled' => 'admin-badge-cancelled',
                                'preparing' => 'admin-badge-preparing',
                                default => 'admin-badge-pending',
                            };
                        @endphp
                        <li class="flex items-center justify-between gap-4 py-3.5 text-sm">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-gray-900">{{ $order->order_number }}</p>
                                <p class="truncate text-gray-500">{{ $order->customer_name }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="font-semibold text-gray-900">{{ $order->formattedTotal() }}</p>
                                <span class="{{ $recentStatusClass }} mt-1">{{ $order->statusLabel() }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
