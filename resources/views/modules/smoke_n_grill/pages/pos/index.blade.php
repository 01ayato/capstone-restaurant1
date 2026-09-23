@extends('layouts.app')

@section('title', 'POS — ' . config('app.name', 'Laravel'))

@section('page-header')
    <div class="flex flex-wrap items-start justify-between gap-3 lg:gap-2">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-600">Counter service</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900 lg:text-xl">Point of Sale</h1>
            <p class="mt-1 text-sm text-gray-500 lg:mt-0.5 lg:text-xs">Take orders at the counter</p>
        </div>
        <a
            href="{{ route('page.smoke_n_grill.orders.index') }}"
            class="admin-btn-secondary lg:min-h-9 lg:px-3 lg:py-1.5 lg:text-xs"
        >
            <i class="fa-solid fa-receipt text-xs" aria-hidden="true"></i>
            View Orders
        </a>
    </div>
@endsection

@section('content')
    <script type="application/json" id="pos-page-initial">@json($posInitial)</script>

    <div
        x-data="posPage()"
        x-on:keydown.window.escape="closeCheckout()"
        class="grid gap-4 lg:h-[calc(100dvh-12.5rem)] lg:min-h-[28rem] lg:grid-cols-[minmax(0,1fr)_18rem] lg:overflow-hidden xl:grid-cols-[minmax(0,1fr)_20rem]"
    >
        {{-- Menu grid --}}
        <div class="admin-table-shell flex min-w-0 flex-col lg:min-h-0">
            <div class="flex shrink-0 flex-wrap gap-2 border-b border-gray-200 p-4 lg:gap-1.5 lg:p-3">
                <template x-for="category in categories" :key="category.id">
                    <button
                        type="button"
                        class="rounded-full px-4 py-1.5 text-sm font-semibold transition lg:px-3 lg:py-1 lg:text-xs"
                        x-bind:class="activeCategoryId === category.id ? 'bg-[#0D2232] text-white shadow-sm' : 'border border-gray-200 bg-white text-gray-700 hover:bg-slate-50'"
                        x-on:click="activeCategoryId = category.id"
                        x-text="category.name"
                    ></button>
                </template>
            </div>

            <div class="grid min-h-0 flex-1 auto-rows-auto content-start items-start grid-cols-[repeat(auto-fill,minmax(10rem,1fr))] gap-3 overflow-y-auto p-4 lg:gap-3 lg:p-3">
                <template x-if="!activeCategory || activeCategory.items.length === 0">
                    <div class="col-span-full py-16 text-center text-sm text-gray-500 lg:py-10">No menu items available.</div>
                </template>
                <template x-for="item in (activeCategory?.items ?? [])" :key="item.id">
                    <article class="flex w-full flex-col self-start overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-orange-200 hover:shadow-md">
                        <div class="aspect-[4/3] overflow-hidden bg-slate-100 lg:aspect-[5/3]">
                            <img x-bind:src="item.image" x-bind:alt="item.name" class="size-full object-cover">
                        </div>
                        <div class="p-4 lg:p-3">
                            <h3 class="font-semibold text-zinc-900 lg:text-sm" x-text="item.name"></h3>
                            <p class="mt-1 text-xs text-zinc-500" x-show="item.tracks_inventory" x-text="'Stock: ' + item.stock_level"></p>
                            <p class="mt-1 text-xs font-medium text-amber-700" x-show="item.is_low_stock">Low stock</p>
                            <p class="mt-1 font-bold text-zinc-900 lg:text-sm" x-text="item.price_label"></p>
                            <button
                                type="button"
                                class="admin-btn-primary mt-3 w-full lg:mt-2 lg:min-h-9 lg:py-2"
                                x-on:click="addItem(item)"
                            >
                                Add to order
                            </button>
                        </div>
                    </article>
                </template>
            </div>
        </div>

        {{-- Cart panel --}}
        <div class="admin-table-shell flex w-full max-h-[calc(100dvh-10rem)] min-h-[20rem] flex-col self-start lg:max-h-none lg:h-full lg:min-h-0 lg:self-stretch">
            <div class="shrink-0 border-b border-gray-200 bg-slate-50 px-5 py-4 lg:px-4 lg:py-3">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-gray-900 lg:text-sm">Current Order</h2>
                        <p class="mt-0.5 text-xs text-gray-500 lg:hidden">Tap items to add, then review when ready</p>
                    </div>
                    <span
                        class="inline-flex min-w-[4.5rem] items-center justify-center rounded-full bg-[#0D2232] px-3 py-1 text-xs font-bold text-white"
                        x-text="`${cartCount} ${cartCount === 1 ? 'item' : 'items'}`"
                    ></span>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4 lg:px-4 lg:py-3">
                <template x-if="cart.length === 0">
                    <div class="flex h-full min-h-52 flex-col items-center justify-center rounded-xl border border-dashed border-gray-200 bg-slate-50 px-6 text-center lg:min-h-40">
                        <div class="mb-3 flex size-12 items-center justify-center rounded-full bg-white shadow-sm">
                            <i class="fa-solid fa-cart-shopping text-lg text-gray-300" aria-hidden="true"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-700">No items yet</p>
                        <p class="mt-1 text-xs leading-relaxed text-gray-500">Select menu items on the left to build this order.</p>
                    </div>
                </template>

                <div class="space-y-2">
                    <template x-for="line in cart" :key="line.menu_item_id">
                        <article class="flex items-center gap-3 rounded-lg border border-gray-200 bg-slate-50/60 p-3 lg:gap-2.5 lg:p-2.5">
                            <img
                                x-bind:src="line.image"
                                x-bind:alt="line.name"
                                class="size-11 shrink-0 rounded-md border border-gray-200 bg-white object-cover lg:size-9"
                            >
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <h3 class="truncate text-sm font-semibold text-gray-900" x-text="line.name"></h3>
                                    <button
                                        type="button"
                                        class="shrink-0 rounded p-0.5 text-gray-400 transition hover:text-red-600"
                                        title="Remove"
                                        x-on:click="remove(line)"
                                    >
                                        <i class="fa-solid fa-xmark text-sm" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="mt-1.5 flex items-center justify-between gap-2">
                                    <div class="inline-flex items-center rounded-md border border-gray-200 bg-white text-xs">
                                        <button type="button" class="flex size-7 items-center justify-center text-gray-600 hover:bg-slate-50" x-on:click="decrement(line)">−</button>
                                        <span class="min-w-[1.5rem] text-center font-bold text-gray-900" x-text="line.quantity"></span>
                                        <button type="button" class="flex size-7 items-center justify-center text-gray-600 hover:bg-slate-50" x-on:click="increment(line)">+</button>
                                    </div>
                                    <p class="text-sm font-bold tabular-nums text-gray-900" x-text="formatMoney(line.price * line.quantity)"></p>
                                </div>
                            </div>
                        </article>
                    </template>
                </div>
            </div>

            <div class="shrink-0 border-t border-gray-200 bg-white px-5 py-4 lg:px-4 lg:py-3 dark:border-white/10 dark:bg-[#112a3d]">
                {{-- Preset Discount Buttons --}}
                <div class="mb-3 space-y-1.5">
                    <div class="flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-300">
                        <span>Discount</span>
                        <span x-show="selectedDiscountRate > 0" class="text-xs font-bold text-emerald-600 dark:text-emerald-400" x-text="`${selectedDiscountLabel} (-${formatMoney(discountAmount)})`"></span>
                    </div>
                    <div class="grid grid-cols-3 gap-1.5">
                        <template x-for="preset in discountPresets" :key="'cart-discount-' + preset.label">
                            <button
                                type="button"
                                class="rounded-lg border px-2 py-1.5 text-xs font-semibold transition text-center"
                                x-bind:class="selectedDiscountRate === preset.rate
                                    ? 'border-[#0D2232] bg-[#0D2232] text-white shadow-sm dark:border-orange-500 dark:bg-orange-600'
                                    : 'border-gray-200 bg-white text-gray-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10'"
                                x-on:click="setDiscount(preset.rate, preset.label)"
                                x-text="preset.label"
                            ></button>
                        </template>
                    </div>
                </div>

                {{-- Totals Breakdown --}}
                <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400 lg:text-xs">
                    <div class="flex items-center justify-between">
                        <span>Subtotal</span>
                        <span class="tabular-nums font-medium text-gray-900 dark:text-white" x-text="formatMoney(subtotal)"></span>
                    </div>
                    <div x-show="selectedDiscountRate > 0" class="flex items-center justify-between font-medium text-emerald-600 dark:text-emerald-400">
                        <span x-text="`Discount (${selectedDiscountLabel})`"></span>
                        <span class="tabular-nums" x-text="`-${formatMoney(discountAmount)}`"></span>
                    </div>
                    <div class="flex items-center justify-between pt-1 border-t border-gray-100 dark:border-white/10 text-base font-bold text-gray-900 dark:text-white lg:text-sm">
                        <span>Total</span>
                        <span class="tabular-nums font-extrabold text-[#0D2232] dark:text-orange-400" x-text="formatMoney(total)"></span>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2 lg:mt-3">
                    <button
                        type="button"
                        class="admin-btn-secondary min-h-11 lg:min-h-9 lg:px-3 lg:py-2 lg:text-xs"
                        x-on:click="clearCart()"
                        x-bind:disabled="cart.length === 0"
                    >
                        Clear
                    </button>
                    <button
                        type="button"
                        class="admin-btn-accent min-h-11 lg:min-h-9 lg:px-3 lg:py-2 lg:text-xs"
                        x-on:click="openCheckout()"
                        x-bind:disabled="cart.length === 0"
                    >
                        Review Order
                    </button>
                </div>
            </div>
        </div>

        {{-- Checkout sidebar overlay --}}
        <div
            x-show="checkoutOpen"
            x-cloak
            class="fixed inset-0 z-50"
            aria-modal="true"
            role="dialog"
        >
            <div
                class="absolute inset-0 bg-[#0D2232]/40 backdrop-blur-[1px] transition-opacity"
                x-show="checkoutOpen"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                x-on:click="closeCheckout()"
            ></div>

            <aside
                class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col border-l border-gray-200 bg-white shadow-2xl lg:max-w-sm dark:border-white/10 dark:bg-[#112a3d]"
                x-show="checkoutOpen"
                x-transition:enter="transform transition ease-out duration-300"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
            >
                {{-- Sidebar header --}}
                <div class="flex shrink-0 items-center justify-between border-b border-gray-200 bg-slate-50 px-5 py-4 dark:border-white/10 dark:bg-[#0d2232]">
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white" x-text="checkoutStep === 'overview' ? 'Order Overview' : 'Confirm Order'"></h2>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400" x-text="checkoutStep === 'overview' ? 'Review items and order details' : 'Verify everything before completing'"></p>
                    </div>
                    <button
                        type="button"
                        class="flex size-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-slate-100 hover:text-gray-700 dark:border-white/10 dark:bg-white/5 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-white"
                        x-on:click="closeCheckout()"
                        aria-label="Close"
                    >
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                {{-- Step indicator --}}
                <div class="flex shrink-0 gap-2 border-b border-gray-100 px-5 py-3 dark:border-white/10">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
                        x-bind:class="checkoutStep === 'overview' ? 'bg-[#0D2232] text-white dark:bg-orange-600' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300'"
                    >
                        <span class="flex size-4 items-center justify-center rounded-full text-[10px]" x-bind:class="checkoutStep === 'confirm' ? 'bg-emerald-600 text-white' : 'bg-white/20'">1</span>
                        Overview
                    </span>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
                        x-bind:class="checkoutStep === 'confirm' ? 'bg-[#0D2232] text-white dark:bg-orange-600' : 'bg-slate-100 text-gray-500 dark:bg-white/5 dark:text-gray-400'"
                    >
                        <span class="flex size-4 items-center justify-center rounded-full text-[10px]" x-bind:class="checkoutStep === 'confirm' ? 'bg-white/20' : 'bg-gray-300 text-gray-600 dark:bg-white/10 dark:text-gray-300'">2</span>
                        Confirmation
                    </span>
                </div>

                {{-- Step 1: Overview --}}
                <div x-show="checkoutStep === 'overview'" class="flex min-h-0 flex-1 flex-col">
                    <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-5 py-4">
                        <section>
                            <h3 class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Order items</h3>
                            <ul class="mt-3 space-y-2">
                                <template x-for="line in cart" :key="'overview-' + line.menu_item_id">
                                    <li class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-slate-50 px-3 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-900 dark:text-white" x-text="line.name"></p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                <span x-text="line.quantity"></span> × <span x-text="line.price_label"></span>
                                            </p>
                                        </div>
                                        <span class="shrink-0 font-bold tabular-nums text-gray-900 dark:text-white" x-text="formatMoney(line.price * line.quantity)"></span>
                                    </li>
                                </template>
                            </ul>
                        </section>

                        <section>
                            <h3 class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Customer</h3>
                            <div class="mt-3 grid gap-3">
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Name</label>
                                    <input type="text" x-model="customerName" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-orange-500 focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-[#0d2232] dark:text-white">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Contact number</label>
                                    <input type="text" x-model="contactNumber" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-orange-500 focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-[#0d2232] dark:text-white">
                                </div>
                            </div>
                        </section>

                        <section>
                            <h3 class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Order details</h3>
                            <div class="mt-3 grid gap-3">
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Order type</label>
                                    <select x-model="orderType" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-orange-500 focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-[#0d2232] dark:text-white">
                                        <option value="dine_in">Dine-in</option>
                                        <option value="takeaway">Takeaway</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Payment method</label>
                                    <select x-model="paymentMethod" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-orange-500 focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-[#0d2232] dark:text-white">
                                        @foreach ($paymentMethods as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Notes <span class="font-normal text-gray-400 dark:text-gray-500">(optional)</span></label>
                                    <textarea x-model="notes" rows="2" placeholder="Special instructions" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-orange-500 focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-[#0d2232] dark:text-white dark:placeholder:text-gray-500"></textarea>
                                </div>
                            </div>
                        </section>

                        {{-- Preset Discount Buttons in Drawer --}}
                        <section>
                            <div class="flex items-center justify-between">
                                <h3 class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Discount</h3>
                                <span x-show="selectedDiscountRate > 0" class="text-xs font-bold text-emerald-600 dark:text-emerald-400" x-text="`${selectedDiscountLabel} (-${formatMoney(discountAmount)})`"></span>
                            </div>
                            <div class="mt-2.5 grid grid-cols-3 gap-2">
                                <template x-for="preset in discountPresets" :key="'drawer-discount-' + preset.label">
                                    <button
                                        type="button"
                                        class="rounded-lg border px-2.5 py-2 text-xs font-semibold transition text-center"
                                        x-bind:class="selectedDiscountRate === preset.rate
                                            ? 'border-[#0D2232] bg-[#0D2232] text-white shadow-sm dark:border-orange-500 dark:bg-orange-600'
                                            : 'border-gray-200 bg-white text-gray-700 hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10'"
                                        x-on:click="setDiscount(preset.rate, preset.label)"
                                        x-text="preset.label"
                                    ></button>
                                </template>
                            </div>
                        </section>
                    </div>

                    {{-- Step 1 Footer: Cash Tendered, Change, and Totals --}}
                    <div class="shrink-0 border-t border-gray-200 bg-white px-5 py-4 dark:border-white/10 dark:bg-[#112a3d]">
                        <template x-if="paymentMethod === 'cash'">
                            <div class="mb-4 space-y-3">
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                            Cash Tendered <span class="font-normal text-gray-400 dark:text-gray-500">(Cash Given)</span>
                                        </label>
                                        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400">Due: <strong class="text-gray-900 dark:text-white" x-text="formatMoney(total)"></strong></span>
                                    </div>
                                    <div class="relative rounded-lg shadow-sm">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <span class="text-sm font-bold text-gray-400 dark:text-gray-500">₱</span>
                                        </div>
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            x-model="amountTendered"
                                            placeholder="0.00"
                                            class="w-full rounded-lg border border-gray-200 bg-white pl-8 pr-3 py-2.5 text-sm font-semibold tabular-nums text-gray-900 shadow-sm focus:border-orange-500 focus:ring-1 focus:ring-orange-500 dark:border-white/10 dark:bg-[#0d2232] dark:text-white dark:placeholder:text-gray-500"
                                        >
                                    </div>

                                    {{-- Quick denomination buttons --}}
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        <button
                                            type="button"
                                            class="rounded-md border border-gray-200 bg-slate-50 px-2 py-1 text-[11px] font-semibold text-gray-700 transition hover:bg-slate-100 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10"
                                            x-on:click="setExactTendered()"
                                        >
                                            Exact
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-md border border-gray-200 bg-slate-50 px-2 py-1 text-[11px] font-medium text-gray-700 transition hover:bg-slate-100 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10"
                                            x-on:click="setTendered(100)"
                                        >
                                            ₱100
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-md border border-gray-200 bg-slate-50 px-2 py-1 text-[11px] font-medium text-gray-700 transition hover:bg-slate-100 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10"
                                            x-on:click="setTendered(200)"
                                        >
                                            ₱200
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-md border border-gray-200 bg-slate-50 px-2 py-1 text-[11px] font-medium text-gray-700 transition hover:bg-slate-100 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10"
                                            x-on:click="setTendered(500)"
                                        >
                                            ₱500
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-md border border-gray-200 bg-slate-50 px-2 py-1 text-[11px] font-medium text-gray-700 transition hover:bg-slate-100 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10"
                                            x-on:click="setTendered(1000)"
                                        >
                                            ₱1,000
                                        </button>
                                    </div>
                                </div>

                                {{-- Automatic Change Calculator Box --}}
                                <div
                                    class="rounded-xl border p-3 transition"
                                    x-bind:class="{
                                        'border-emerald-200 bg-emerald-50/80 text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300': isTenderedSufficient && numericAmountTendered > 0,
                                        'border-rose-200 bg-rose-50/80 text-rose-900 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300': !isTenderedSufficient && numericAmountTendered > 0,
                                        'border-gray-200 bg-slate-50 text-gray-700 dark:border-white/10 dark:bg-white/5 dark:text-gray-300': numericAmountTendered === 0
                                    }"
                                >
                                    <div class="flex items-center justify-between text-sm">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold">Change (Sukli)</span>
                                            <span x-show="isTenderedSufficient && numericAmountTendered > 0" class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300">
                                                Ready
                                            </span>
                                        </div>
                                        <span
                                            class="text-lg font-extrabold tabular-nums"
                                            x-bind:class="isTenderedSufficient ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                                            x-text="formatMoney(Math.max(change, 0))"
                                        ></span>
                                    </div>
                                    <div x-show="!isTenderedSufficient && numericAmountTendered > 0" class="mt-1 flex items-center justify-between text-xs font-semibold text-rose-600 dark:text-rose-400">
                                        <span>Insufficient amount</span>
                                        <span>Short by <strong x-text="formatMoney(shortage)"></strong></span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Order summary totals --}}
                        <div class="space-y-1.5 border-t border-gray-100 pt-3 dark:border-white/10 text-xs">
                            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400">
                                <span>Subtotal</span>
                                <span class="font-medium tabular-nums text-gray-900 dark:text-white" x-text="formatMoney(subtotal)"></span>
                            </div>
                            <div x-show="selectedDiscountRate > 0" class="flex items-center justify-between font-medium text-emerald-600 dark:text-emerald-400">
                                <span x-text="`Discount (${selectedDiscountLabel})`"></span>
                                <span class="tabular-nums" x-text="`-${formatMoney(discountAmount)}`"></span>
                            </div>
                            <div class="flex items-center justify-between pt-1 text-base font-bold text-gray-900 dark:text-white">
                                <span>Total to Pay</span>
                                <span class="text-lg font-black tabular-nums text-[#0D2232] dark:text-orange-400" x-text="formatMoney(total)"></span>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="mt-4 w-full rounded-lg bg-[#0D2232] px-4 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#16364D] dark:bg-orange-600 dark:hover:bg-orange-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            x-on:click="goToConfirm()"
                            x-bind:disabled="paymentMethod === 'cash' && !isTenderedSufficient"
                        >
                            Continue to Confirmation
                        </button>
                    </div>
                </div>

                {{-- Step 2: Confirmation --}}
                <div x-show="checkoutStep === 'confirm'" class="flex min-h-0 flex-1 flex-col">
                    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                            <p class="font-semibold">Ready to complete this order?</p>
                            <p class="mt-1 text-xs text-amber-800 dark:text-amber-400">Please verify the summary below before proceeding.</p>
                        </div>

                        <dl class="mt-5 space-y-3 text-sm">
                            <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-white/10">
                                <dt class="text-gray-500 dark:text-gray-400">Customer</dt>
                                <dd class="text-right font-medium text-gray-900 dark:text-white" x-text="customerName"></dd>
                            </div>
                            <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-white/10">
                                <dt class="text-gray-500 dark:text-gray-400">Contact</dt>
                                <dd class="text-right text-gray-900 dark:text-white" x-text="contactNumber"></dd>
                            </div>
                            <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-white/10">
                                <dt class="text-gray-500 dark:text-gray-400">Order type</dt>
                                <dd class="text-right text-gray-900 dark:text-white" x-text="orderTypeLabel()"></dd>
                            </div>
                            <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-white/10">
                                <dt class="text-gray-500 dark:text-gray-400">Payment</dt>
                                <dd class="text-right font-medium text-gray-900 dark:text-white" x-text="paymentMethodLabel()"></dd>
                            </div>
                            <div x-show="selectedDiscountRate > 0" class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-white/10">
                                <dt class="text-gray-500 dark:text-gray-400">Discount</dt>
                                <dd class="text-right font-bold text-emerald-600 dark:text-emerald-400" x-text="`${selectedDiscountLabel} (-${formatMoney(discountAmount)})`"></dd>
                            </div>
                            <template x-if="paymentMethod === 'cash'">
                                <div class="space-y-2 border-b border-gray-100 pb-3 dark:border-white/10">
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Cash Tendered</dt>
                                        <dd class="text-right font-medium tabular-nums text-gray-900 dark:text-white" x-text="formatMoney(numericAmountTendered)"></dd>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <dt class="font-semibold text-emerald-700 dark:text-emerald-400">Change (Sukli)</dt>
                                        <dd class="text-right font-extrabold tabular-nums text-emerald-700 dark:text-emerald-400" x-text="formatMoney(Math.max(change, 0))"></dd>
                                    </div>
                                </div>
                            </template>
                            <template x-if="notes.trim()">
                                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-white/10">
                                    <dt class="shrink-0 text-gray-500 dark:text-gray-400">Notes</dt>
                                    <dd class="text-right text-gray-900 dark:text-white" x-text="notes"></dd>
                                </div>
                            </template>
                        </dl>

                        <h3 class="mt-6 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Items summary</h3>
                        <ul class="mt-3 space-y-2">
                            <template x-for="line in cart" :key="'confirm-' + line.menu_item_id">
                                <li class="flex items-center justify-between gap-3 text-sm">
                                    <span class="text-gray-700 dark:text-gray-300">
                                        <span class="font-semibold text-gray-900 dark:text-white" x-text="line.name"></span>
                                        <span class="text-gray-500 dark:text-gray-400"> × </span>
                                        <span x-text="line.quantity"></span>
                                    </span>
                                    <span class="font-medium tabular-nums text-gray-900 dark:text-white" x-text="formatMoney(line.price * line.quantity)"></span>
                                </li>
                            </template>
                        </ul>
                    </div>

                    {{-- Step 2 Footer --}}
                    <div class="shrink-0 border-t border-gray-200 bg-white px-5 py-4 dark:border-white/10 dark:bg-[#112a3d]">
                        <div class="space-y-1.5 mb-4 text-xs">
                            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400">
                                <span>Subtotal</span>
                                <span class="font-medium tabular-nums text-gray-900 dark:text-white" x-text="formatMoney(subtotal)"></span>
                            </div>
                            <div x-show="selectedDiscountRate > 0" class="flex items-center justify-between font-medium text-emerald-600 dark:text-emerald-400">
                                <span x-text="`Discount (${selectedDiscountLabel})`"></span>
                                <span class="tabular-nums" x-text="`-${formatMoney(discountAmount)}`"></span>
                            </div>
                            <div class="flex items-center justify-between pt-1 text-base font-bold text-gray-900 dark:text-white">
                                <span>Final Total</span>
                                <span class="text-lg font-black tabular-nums text-[#0D2232] dark:text-orange-400" x-text="formatMoney(total)"></span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                class="rounded-lg border border-gray-200 bg-white px-4 py-3.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50 disabled:opacity-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10"
                                x-on:click="backToOverview()"
                                x-bind:disabled="submitting"
                            >
                                Back
                            </button>
                            <button
                                type="button"
                                class="rounded-lg bg-emerald-600 px-4 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                                x-on:click="completeSale()"
                                x-bind:disabled="submitting || (paymentMethod === 'cash' && !isTenderedSufficient)"
                            >
                                <span x-show="!submitting">Proceed to Complete</span>
                                <span x-show="submitting" x-cloak>Processing...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
@endsection
