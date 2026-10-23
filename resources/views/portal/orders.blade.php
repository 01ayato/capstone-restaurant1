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
                            @php
                                $estimatedPrepTime = $order->final_eta ?? $order->system_eta;
                            @endphp
                            @if ($estimatedPrepTime !== null)
                                <div class="mt-4 flex items-start gap-3 rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 dark:border-orange-400/30 dark:bg-slate-800">
                                    <i class="fa-regular fa-clock mt-0.5 text-orange-600 dark:text-orange-400" aria-hidden="true"></i>
                                    <p class="text-sm text-slate-900 dark:text-slate-100">
                                        <span class="font-semibold">Estimated Preparation Time:</span>
                                        {{ $estimatedPrepTime }} {{ (int) $estimatedPrepTime === 1 ? 'minute' : 'minutes' }}
                                    </p>
                                </div>
                            @endif
                            <p class="mt-4 text-right text-lg font-bold text-gray-900">Total: {{ $order->formattedTotal() }}</p>
                            @if ($order->status === \App\Models\Order::STATUS_COMPLETED)
                                <div class="mt-3 text-right">
                                    <a href="{{ route('feedback.create') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-orange-600 hover:text-orange-500">
                                        <i class="fa-solid fa-star text-xs" aria-hidden="true"></i> Leave a Review
                                    </a>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
