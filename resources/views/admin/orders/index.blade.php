@extends('layouts.app')

@section('title', 'Order Management')

@section('page-header')
    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-600">Operations</p>
    <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Order Management</h1>
    <p class="mt-1 text-sm text-gray-500">Track, update, and review every restaurant order.</p>
@endsection

@section('content')
    @php
        $statusColors = [
            \App\Models\Order::STATUS_PENDING => 'bg-amber-100 text-amber-700',
            \App\Models\Order::STATUS_PREPARING => 'bg-blue-100 text-blue-700',
            \App\Models\Order::STATUS_READY => 'bg-blue-100 text-blue-700',
            \App\Models\Order::STATUS_COMPLETED => 'bg-green-100 text-green-700',
            \App\Models\Order::STATUS_CANCELLED => 'bg-red-100 text-red-700',
        ];
    @endphp

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route($routes['index']) }}" class="rounded-full px-3.5 py-2 text-xs font-semibold transition {{ ! $currentStatus ? 'bg-[#0D2232] text-white shadow-sm' : 'border border-gray-200 bg-white text-gray-600 hover:bg-slate-50' }}">All</a>
        @foreach ($statuses as $value => $label)
            <a href="{{ route($routes['index'], ['status' => $value]) }}" class="rounded-full px-3.5 py-2 text-xs font-semibold transition {{ $currentStatus === $value ? 'bg-[#0D2232] text-white shadow-sm' : 'border border-gray-200 bg-white text-gray-600 hover:bg-slate-50' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="admin-table-shell overflow-x-auto">
        <table class="min-w-[760px] divide-y divide-gray-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Order</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Customer</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Total</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($orders as $order)
                    <tr class="align-top">
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $order->order_number }}</p>
                            <p class="text-xs text-gray-500">{{ $order->created_at->format('M d, Y h:i A') }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $order->customer_name }}</td>
                        <td class="px-4 py-3 font-medium">{{ $order->formattedTotal() }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusColors[$order->status] ?? 'bg-slate-100 text-gray-700' }}">
                                {{ $order->statusLabel() }}
                            </span>
                            <form action="{{ route($routes['status'], $order) }}" method="post" class="mt-2">
                                @csrf
                                @method('PATCH')
                                <select
                                    name="status"
                                    class="w-full min-w-[10rem] rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700"
                                    onchange="this.form.submit()"
                                >
                                    @foreach ($statuses as $value => $label)
                                        <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                <a
                                    href="{{ route($routes['show'], $order) }}"
                                    class="inline-flex rounded-[10px] border border-[#204865]/20 bg-[#204865]/5 px-3 py-1.5 text-xs font-semibold text-[#16364D] shadow-sm transition hover:bg-[#204865]/10"
                                >
                                    View
                                </a>
                                <a
                                    href="{{ route($routes['receipt'], $order) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-800 shadow-sm transition hover:bg-zinc-50"
                                >
                                    <i class="fa-solid fa-print text-[10px]" aria-hidden="true"></i>
                                    Print
                                </a>
                                <form
                                    action="{{ route($routes['destroy'], $order) }}"
                                    method="post"
                                    class="inline"
                                    data-confirm-title="Delete this order?"
                                    data-confirm-text="This will permanently remove {{ $order->order_number }}."
                                    data-confirm-yes="Delete"
                                    data-confirm-no="Cancel"
                                    x-on:submit.prevent="(el => confirmDialog({ title: el.dataset.confirmTitle, text: el.dataset.confirmText, confirmButtonText: el.dataset.confirmYes, cancelButtonText: el.dataset.confirmNo, icon: 'warning' }).then(ok => ok && el.submit()))($el)"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="inline-flex rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-600 shadow-sm transition hover:bg-red-50"
                                    >
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No orders found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
@endsection
