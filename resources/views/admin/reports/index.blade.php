@extends('layouts.app')

@section('title', 'Sales Reports')

@section('page-header')
    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-600">Performance</p>
    <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Sales Reports</h1>
    <p class="mt-1 text-sm text-gray-500">Understand revenue, order volume, and best-selling items.</p>
@endsection

@section('content')
    <form method="get" class="admin-card mb-6 flex flex-wrap items-end gap-4 p-5">
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-500">From</label>
            <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-500">To</label>
            <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
        </div>
        <button type="submit" class="admin-btn-primary">Apply dates</button>
    </form>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="admin-card admin-card-hover">
            <p class="text-xs font-bold uppercase text-gray-400">Total Revenue</p>
            <p class="mt-2 text-2xl font-bold text-emerald-600">{{ \App\Support\Peso::format($totalRevenue) }}</p>
        </div>
        <div class="admin-card admin-card-hover">
            <p class="text-xs font-bold uppercase text-gray-400">Completed Orders</p>
            <p class="mt-2 text-2xl font-bold">{{ number_format($orderCount) }}</p>
        </div>
        <div class="admin-card admin-card-hover">
            <p class="text-xs font-bold uppercase text-gray-400">Avg Order Value</p>
            <p class="mt-2 text-2xl font-bold">{{ \App\Support\Peso::format($avgOrderValue) }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="admin-card overflow-x-auto">
            <h2 class="text-base font-bold">Daily Sales</h2>
            <table class="mt-4 min-w-[420px] text-sm">
                <thead><tr class="border-b text-left text-gray-500"><th class="pb-2">Date</th><th class="pb-2">Orders</th><th class="pb-2 text-right">Revenue</th></tr></thead>
                <tbody>
                    @forelse ($dailySales as $day)
                        <tr class="border-b border-slate-100"><td class="py-2">{{ $day->date }}</td><td class="py-2">{{ $day->orders }}</td><td class="py-2 text-right">{{ \App\Support\Peso::format($day->revenue) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="py-4 text-center text-gray-500">No data for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="admin-card overflow-x-auto">
            <h2 class="text-base font-bold">Top Items</h2>
            <table class="mt-4 min-w-[420px] text-sm">
                <thead><tr class="border-b text-left text-gray-500"><th class="pb-2">Item</th><th class="pb-2">Qty</th><th class="pb-2 text-right">Revenue</th></tr></thead>
                <tbody>
                    @forelse ($topItems as $item)
                        <tr class="border-b border-slate-100"><td class="py-2">{{ $item->item_name }}</td><td class="py-2">{{ $item->total_qty }}</td><td class="py-2 text-right">{{ \App\Support\Peso::format($item->revenue) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="py-4 text-center text-gray-500">No data yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
