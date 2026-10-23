@extends('layouts.kitchen')

@section('title', 'Kitchen Display')

@section('page-header')
    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-600">Kitchen</p>
    <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Kitchen Display</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-white/60">Review incoming orders and keep preparation moving.</p>
@endsection

@section('content')
    <div class="grid min-h-0 flex-1 grid-cols-1 content-start items-start gap-6 lg:grid-cols-2">
        <section aria-labelledby="pending-orders-heading">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 id="pending-orders-heading" class="text-lg font-bold text-gray-900 dark:text-white">Pending Orders</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-white/55">Review and accept new orders.</p>
                </div>
                <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-amber-100 px-2 text-sm font-bold text-amber-800 dark:bg-amber-400/15 dark:text-amber-300">{{ $orders->where('status', \App\Models\Order::STATUS_PENDING)->count() }}</span>
            </div>
    
            <div class="space-y-4">
                @forelse ($orders->where('status', \App\Models\Order::STATUS_PENDING) as $order)
                    <article
                        x-data="{
                            eta: {{ max(1, (int) ($order->system_eta ?? 1)) }},
                            submitting: false,
                            error: '',
                            adjustEta(amount) { this.eta = Math.max(1, Number(this.eta || 1) + amount); },
                            async acceptOrder(url) {
                                this.submitting = true;
                                this.error = '';
                                try {
                                    const response = await fetch(url, {
                                        method: 'POST',
                                        headers: {
                                            'Accept': 'application/json',
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                        },
                                        body: JSON.stringify({ final_eta: Number(this.eta) }),
                                    });
                                    const result = await response.json();
                                    if (!response.ok) {
                                        throw new Error(result.message || 'Could not accept this order.');
                                    }
                                    window.location.reload();
                                } catch (error) {
                                    this.error = error.message || 'Could not accept this order.';
                                    this.submitting = false;
                                }
                            },
                        }"
                        class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-[#112a3d]"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-white/10">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-white/50">Order</p>
                                <h3 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">#{{ $order->order_number }}</h3>
                            </div>
                            <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 dark:bg-amber-400/10 dark:text-amber-300">
                                <span class="h-2 w-2 rounded-full bg-amber-500" aria-hidden="true"></span>
                                New order
                            </span>
                        </div>

                        <div class="py-4">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Items</h4>
                            <ul class="mt-3 space-y-3 text-sm">
                                @foreach ($order->items as $item)
                                    <li class="flex items-start justify-between gap-4">
                                        <span class="text-gray-700 dark:text-white/80"><span class="font-semibold">{{ $item->quantity }}×</span> {{ $item->item_name }}</span>
                                        @if ($item->special_instructions)
                                            <span class="shrink-0 text-xs text-gray-500 dark:text-white/50">{{ $item->special_instructions }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="rounded-xl bg-slate-50 p-4 dark:bg-white/5">
                            <label for="suggested-eta-{{ $order->id }}" class="block text-sm font-semibold text-gray-900 dark:text-white">Suggested ETA</label>
                            <p class="mt-1 text-xs text-gray-500 dark:text-white/55">Set the estimated preparation time in minutes.</p>
                            <div class="mt-3 flex items-center gap-2">
                                <button
                                    type="button"
                                    x-on:click="adjustEta(-5)"
                                    class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-orange-500/40 dark:border-white/10 dark:bg-[#0d2232] dark:text-white dark:hover:bg-white/10"
                                    aria-label="Decrease suggested ETA by 5 minutes"
                                >-5</button>
                                <input
                                    id="suggested-eta-{{ $order->id }}"
                                    type="number"
                                    min="1"
                                    step="1"
                                    x-model.number="eta"
                                    class="h-10 w-24 rounded-lg border border-gray-200 bg-white px-3 text-center text-sm font-semibold text-gray-900 outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 dark:border-white/10 dark:bg-[#0d2232] dark:text-white"
                                    aria-describedby="eta-unit-{{ $order->id }}"
                                >
                                <span id="eta-unit-{{ $order->id }}" class="text-sm text-gray-500 dark:text-white/55">min</span>
                                <button
                                    type="button"
                                    x-on:click="adjustEta(5)"
                                    class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-orange-500/40 dark:border-white/10 dark:bg-[#0d2232] dark:text-white dark:hover:bg-white/10"
                                    aria-label="Increase suggested ETA by 5 minutes"
                                >+5</button>
                            </div>
                        </div>

                        <p x-cloak x-show="error" x-text="error" role="alert" class="mt-3 text-sm font-medium text-red-600 dark:text-red-400"></p>
                        <button
                            type="button"
                            x-on:click="acceptOrder(@js(route('kitchen.orders.accept', $order)))"
                            x-bind:disabled="submitting"
                            class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-admin-navy px-4 py-3 text-sm font-semibold text-white transition hover:bg-admin-navy-hover focus:outline-none focus:ring-2 focus:ring-admin-navy/40 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:focus:ring-offset-[#112a3d]"
                        ><span x-show="!submitting">Accept Order</span><span x-cloak x-show="submitting">Accepting...</span></button>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-white/60 px-6 py-10 text-center dark:border-white/15 dark:bg-white/[0.03]">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">No pending kitchen orders</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-white/55">New kitchen orders will appear here.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section aria-labelledby="currently-cooking-heading">
            <div class="mb-4">
                <h2 id="currently-cooking-heading" class="text-lg font-bold text-gray-900 dark:text-white">Currently Cooking</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-white/55">Orders being prepared by the kitchen.</p>
            </div>

            <div class="space-y-4">
                @forelse ($orders->where('status', \App\Models\Order::STATUS_PREPARING) as $order)
                    <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-[#112a3d]">
                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-white/10">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-white/50">Order</p>
                                <h3 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">#{{ $order->order_number }}</h3>
                            </div>
                            <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 dark:bg-blue-400/10 dark:text-blue-300">
                                <span class="h-2 w-2 rounded-full bg-blue-500" aria-hidden="true"></span>
                                Preparing
                            </span>
                        </div>
                        <ul class="mt-4 space-y-3 text-sm">
                            @foreach ($order->items as $item)
                                <li class="flex items-start justify-between gap-4">
                                    <span class="text-gray-700 dark:text-white/80"><span class="font-semibold">{{ $item->quantity }}×</span> {{ $item->item_name }}</span>
                                    @if ($item->special_instructions)
                                        <span class="shrink-0 text-xs text-gray-500 dark:text-white/50">{{ $item->special_instructions }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </article>
                @empty
                    <div class="flex min-h-56 flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-white/60 px-6 py-10 text-center dark:border-white/15 dark:bg-white/[0.03]">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-gray-500 dark:bg-white/10 dark:text-white/60" aria-hidden="true">
                            <i class="fa-solid fa-fire-burner"></i>
                        </span>
                        <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">No orders cooking yet</h3>
                        <p class="mt-1 max-w-xs text-sm text-gray-500 dark:text-white/55">Accepted orders will appear here while they are being prepared.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
