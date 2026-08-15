@extends('layouts.portal')

@section('title', 'Your Cart — ' . $settings->name)

@section('content')
    <section class="px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl">
            @include('portal.partials.table-seat-banner')

            <h1 class="font-display text-3xl font-bold text-gray-900">Your Order</h1>
            <p class="mt-2 text-gray-500">Review items, update quantities, and proceed to checkout.</p>

            @if ($cartItems->isEmpty())
                <div class="mt-12 rounded-2xl border border-gray-200 bg-white p-12 text-center backdrop-blur-sm">
                    <i class="fa-solid fa-bag-shopping text-4xl text-gray-400" aria-hidden="true"></i>
                    <p class="mt-4 text-gray-500">Your cart is empty.</p>
                    <a href="{{ route('menu.index') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-orange-500 px-6 py-3 text-sm font-semibold text-white">
                        Continue Shopping
                    </a>
                </div>
            @else
                <div class="mt-8 space-y-4">
                    @foreach ($cartItems as $line)
                        <div class="rounded-2xl border border-gray-200 bg-white p-5 backdrop-blur-sm">
                            <form action="{{ route('cart.update') }}" method="post" class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                @csrf
                                <input type="hidden" name="menu_item_id" value="{{ $line->item->id }}">
                                <div class="flex gap-4">
                                    <img src="{{ $line->item->imageUrl() }}" alt="{{ $line->item->name }}" class="size-20 rounded-xl object-cover">
                                    <div>
                                        <h3 class="font-semibold text-gray-900">{{ $line->item->name }}</h3>
                                        <p class="text-sm text-orange-600">{{ $line->item->formattedPrice() }} × {{ $line->quantity }}</p>
                                        <p class="mt-1 text-sm font-medium text-gray-900">Line total: {{ \App\Support\Peso::format($line->line_total) }}</p>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-3 sm:items-end">
                                    <div class="flex items-center gap-2">
                                        <label class="sr-only" for="qty-{{ $line->item->id }}">Quantity</label>
                                        <input id="qty-{{ $line->item->id }}" type="number" name="quantity" value="{{ $line->quantity }}" min="0" max="99" class="w-20 rounded-lg border border-gray-200 bg-slate-100 px-3 py-2 text-sm text-gray-900">
                                        <button type="submit" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 hover:border-orange-500/50">Update</button>
                                    </div>
                                    <input type="text" name="instructions" value="{{ $line->instructions }}" placeholder="Special instructions..." class="w-full rounded-lg border border-gray-200 bg-slate-100 px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 sm:w-64">
                                </div>
                            </form>
                            <form action="{{ route('cart.remove') }}" method="post" class="mt-3">
                                @csrf
                                <input type="hidden" name="menu_item_id" value="{{ $line->item->id }}">
                                <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-700">
                                    <i class="fa-solid fa-trash mr-1" aria-hidden="true"></i> Remove
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 rounded-2xl border border-amber-500/20 bg-white shadow-[0_4px_12px_rgba(15,23,42,.05)] p-6">
                    <h2 class="font-display text-lg font-semibold text-orange-600">Order Summary</h2>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between text-gray-500">
                            <dt>Subtotal</dt>
                            <dd class="text-gray-900">{{ \App\Support\Peso::format($subtotal) }}</dd>
                        </div>
                        <div class="flex justify-between text-gray-500">
                            <dt>Delivery Fee</dt>
                            <dd class="text-gray-900">{{ \App\Support\Peso::format($deliveryFee) }}</dd>
                        </div>
                        <div class="flex justify-between border-t border-gray-200 pt-3 text-base font-bold">
                            <dt class="text-gray-900">Total</dt>
                            <dd class="text-orange-600">{{ \App\Support\Peso::format($total) }}</dd>
                        </div>
                    </dl>
                    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('menu.index') }}" class="inline-flex flex-1 items-center justify-center rounded-xl border border-gray-300 px-6 py-3 text-sm font-semibold text-gray-900 transition hover:border-orange-500/50">
                            Continue Shopping
                        </a>
                        @auth
                            @php
                                $activeTable = $currentTable ?? app(\App\Services\TableSessionService::class)->currentTablePayload();
                            @endphp
                            @if (! empty($activeTable))
                                <a href="{{ route('checkout.show') }}" class="inline-flex flex-1 items-center justify-center rounded-xl bg-orange-500 px-6 py-3 text-sm font-semibold text-white transition hover:bg-orange-400">
                                    Checkout
                                </a>
                            @else
                                <button
                                    type="button"
                                    class="inline-flex flex-1 items-center justify-center rounded-xl border border-orange-500/40 bg-orange-500/10 px-6 py-3 text-sm font-semibold text-orange-700"
                                    onclick="window.dispatchEvent(new CustomEvent('open-table-select'))"
                                >
                                    Select table to checkout
                                </button>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="inline-flex flex-1 items-center justify-center rounded-xl bg-orange-500 px-6 py-3 text-sm font-semibold text-white">
                                Sign in to Checkout
                            </a>
                        @endauth
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
