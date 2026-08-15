@extends('layouts.portal')

@section('title', 'My Orders — ' . $settings->name)

@section('content')
    <section class="px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl">
            <h1 class="font-display text-3xl font-bold text-gray-900">My Orders</h1>
            <p class="mt-2 text-gray-500">Track your order status and view order history.</p>

            @if ($orders->isEmpty())
                <div class="mt-12 rounded-2xl border border-gray-200 bg-white p-12 text-center">
                    <p class="text-gray-500">You haven't placed any orders yet.</p>
                    <a href="{{ route('menu.index') }}" class="mt-6 inline-flex rounded-xl bg-orange-500 px-6 py-3 text-sm font-semibold text-white">Browse Menu</a>
                </div>
            @else
                <div class="mt-8 space-y-4">
                    @foreach ($orders as $order)
                        @php
                            $statusColors = [
                                'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                                'preparing' => 'bg-blue-100 text-blue-700 border-blue-200',
                                'ready' => 'bg-blue-100 text-blue-700 border-blue-200',
                                'completed' => 'bg-green-100 text-green-700 border-green-200',
                                'cancelled' => 'bg-red-100 text-red-700 border-red-200',
                            ];
                            $color = $statusColors[$order->status] ?? 'bg-slate-100 text-gray-600 border-gray-200';
                        @endphp
                        <article class="rounded-2xl border border-gray-200 bg-white p-6 backdrop-blur-sm">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-widest text-gray-500">Order ID</p>
                                    <p class="font-mono text-lg font-semibold text-orange-600">{{ $order->order_number }}</p>
                                    <p class="mt-1 text-sm text-gray-500">{{ $order->created_at->format('M d, Y h:i A') }}</p>
                                </div>
                                <span class="inline-flex self-start rounded-full border px-3 py-1 text-xs font-semibold {{ $color }}">
                                    {{ $order->statusLabel() }}
                                </span>
                            </div>
                            <ul class="mt-4 space-y-1 text-sm text-gray-500">
                                @foreach ($order->items as $item)
                                    <li>{{ $item->item_name }} × {{ $item->quantity }} — {{ \App\Support\Peso::format($item->line_total) }}</li>
                                @endforeach
                            </ul>
                            <p class="mt-4 text-right text-lg font-bold text-gray-900">Total: {{ $order->formattedTotal() }}</p>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
