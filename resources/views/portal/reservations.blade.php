@extends('layouts.portal')

@section('title', 'Reserve a Table — ' . ($settings->name ?? "Smoke n' Grill"))

@php
    $reservationsInitial = [
        'groups' => $groupsPayload,
        'menu' => $menuPayload,
        'selectedGroupId' => old('dining_table_id')
            ? $groups->flatMap->diningTables->firstWhere('id', (int) old('dining_table_id'))?->table_group_id
            : ($groups->first()?->id),
        'selectedTableId' => old('dining_table_id'),
        'partySize' => old('party_size', 2),
        'additionalChairs' => old('additional_chairs', 0),
        'reservedAt' => old('reserved_at'),
        'customerName' => old('customer_name', auth()->user()?->name ?? ''),
        'contactNumber' => old('contact_number'),
        'email' => old('email', auth()->user()?->email ?? ''),
        'hasErrors' => $errors->any(),
        'step' => $errors->any() ? 4 : 1,
        'tableDeposit' => $tableDeposit,
        'preorderDeposit' => $preorderDeposit,
        'depositScanUrl' => $depositScanUrl,
        'availabilityUrl' => $availabilityUrl,
        'openingHoursLabel' => $openingHoursLabel,
        'openHour' => $openHour,
        'csrfToken' => csrf_token(),
    ];
@endphp

@section('content')
    <script type="application/json" id="portal-reservations-initial">@json($reservationsInitial)</script>

    <section
        class="px-4 py-10 sm:px-6 lg:px-8"
        x-data="portalReservationsPage(JSON.parse(document.getElementById('portal-reservations-initial')?.textContent || '{}'))"
    >
        <div class="mx-auto max-w-6xl">
            <div class="text-center">
                <p class="text-xs font-bold uppercase tracking-widest text-orange-500">Reservations</p>
                <h1 class="mt-2 font-display text-3xl font-bold text-gray-900 sm:text-4xl">Reserve a Table</h1>
                <p class="mx-auto mt-2 max-w-xl text-sm text-gray-500">
                    Four quick steps — your details, table, pre-order, then a refundable ₱500 deposit (plus ₱500 if you pre-order food).
                </p>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-3">
                {{-- Main form --}}
                <div class="lg:col-span-2">
                    <div id="reservation-form-top" class="rounded-2xl border border-gray-200 bg-white p-5 backdrop-blur-sm sm:p-7">
                        @if ($errors->any())
                            <div class="mb-5 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-700">
                                <ul class="list-inside list-disc space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($groups->isEmpty())
                            <p class="rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm text-gray-500">
                                No tables are available for reservation yet. Please check back later.
                            </p>
                        @else
                            {{-- Step progress --}}
                            <nav aria-label="Reservation steps" class="mb-6">
                                <ol class="flex items-center gap-1 sm:gap-2">
                                    <template x-for="s in steps()" :key="'step-nav-' + s.num">
                                        <li class="flex min-w-0 flex-1 items-center gap-1 sm:gap-2">
                                            <button
                                                type="button"
                                                class="flex min-w-0 flex-1 items-center gap-2 rounded-xl border px-2 py-2.5 text-left transition sm:px-3"
                                                x-bind:class="{
                                                    'border-orange-500/50 bg-orange-500/15': stepStatus(s.num) === 'active',
                                                    'border-emerald-500/30 bg-emerald-500/10': stepStatus(s.num) === 'done',
                                                    'border-gray-200 bg-white hover:bg-slate-100': stepStatus(s.num) === 'upcoming',
                                                }"
                                                x-on:click="goToStep(s.num)"
                                            >
                                                <span
                                                    class="flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                                    x-bind:class="{
                                                        'bg-orange-500 text-white': stepStatus(s.num) === 'active',
                                                        'bg-emerald-500 text-white': stepStatus(s.num) === 'done',
                                                        'bg-slate-100 text-gray-500': stepStatus(s.num) === 'upcoming',
                                                    }"
                                                >
                                                    <template x-if="stepStatus(s.num) === 'done'">
                                                        <i class="fa-solid fa-check text-[10px]" aria-hidden="true"></i>
                                                    </template>
                                                    <template x-if="stepStatus(s.num) !== 'done'">
                                                        <span x-text="s.num"></span>
                                                    </template>
                                                </span>
                                                <span class="hidden min-w-0 sm:block">
                                                    <span class="block truncate text-xs font-bold text-gray-900" x-text="s.label"></span>
                                                </span>
                                            </button>
                                            <div x-show="s.num < 4" class="hidden h-px w-2 shrink-0 bg-slate-100 sm:block"></div>
                                        </li>
                                    </template>
                                </ol>
                                <p class="mt-3 text-center text-xs text-gray-500">
                                    Step <span x-text="step"></span> of 4 —
                                    <span x-text="steps().find(s => s.num === step)?.label"></span>
                                </p>
                            </nav>

                            <form action="{{ route('reservations.store') }}" method="post" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="dining_table_id" x-bind:value="selectedTableId">
                                <input type="hidden" name="customer_name" x-bind:value="customerName">
                                <input type="hidden" name="contact_number" x-bind:value="contactNumber">
                                <input type="hidden" name="email" x-bind:value="email">
                                <input type="hidden" name="party_size" x-bind:value="partySize">
                                <input type="hidden" name="reserved_at" x-bind:value="reservedAt">
                                <input type="hidden" name="additional_chairs" x-bind:value="needsAdditionalChairs() ? additionalChairs : 0">
                                <input type="hidden" name="notes" x-bind:value="compiledOrderNotes()">
                                <input type="hidden" name="has_preorder" x-bind:value="hasOrderItems() ? 1 : 0">
                                <template x-for="(line, index) in orderLines()" :key="'preorder-' + line.item.id">
                                    <div>
                                        <input type="hidden" x-bind:name="'items[' + index + '][menu_item_id]'" x-bind:value="line.item.id">
                                        <input type="hidden" x-bind:name="'items[' + index + '][quantity]'" x-bind:value="line.qty">
                                    </div>
                                </template>

                                {{-- STEP 1: Info --}}
                                <div x-show="step === 1" class="space-y-4">
                                    <div class="rounded-xl border border-gray-200 bg-slate-50 p-4">
                                        <h2 class="flex items-center gap-2 text-base font-bold text-gray-900">
                                            <i class="fa-solid fa-user text-orange-600" aria-hidden="true"></i>
                                            Your information
                                        </h2>
                                        <p class="mt-1 text-xs text-gray-500">We'll use this to confirm your booking.</p>
                                    </div>

                                    <div>
                                        <label for="customer_name" class="mb-1.5 block text-xs font-bold uppercase tracking-widest text-gray-500">Full name</label>
                                        <input id="customer_name" type="text" required x-model="customerName" placeholder="Your name" class="sg-input w-full">
                                    </div>
                                    <div>
                                        <label for="contact_number" class="mb-1.5 block text-xs font-bold uppercase tracking-widest text-gray-500">Mobile number</label>
                                        <input id="contact_number" type="tel" required x-model="contactNumber" placeholder="09XX XXX XXXX" class="sg-input w-full">
                                    </div>
                                    <div>
                                        <label for="email" class="mb-1.5 block text-xs font-bold uppercase tracking-widest text-gray-500">Email <span class="font-normal normal-case">(optional)</span></label>
                                        <input id="email" type="email" x-model="email" placeholder="you@email.com" class="sg-input w-full">
                                    </div>
                                </div>

                                {{-- STEP 2: Table & time --}}
                                <div x-show="step === 2" x-cloak class="space-y-5">
                                    <div class="rounded-xl border border-gray-200 bg-slate-50 p-4">
                                        <h2 class="flex items-center gap-2 text-base font-bold text-gray-900">
                                            <i class="fa-solid fa-chair text-orange-600" aria-hidden="true"></i>
                                            Table &amp; visit time
                                        </h2>
                                        <p class="mt-1 text-xs text-zinc-500">Set your party size and when you're coming, then pick a table.</p>
                                        <p class="mt-2 text-xs text-orange-300/90">
                                            <i class="fa-solid fa-clock mr-1" aria-hidden="true"></i>
                                            Open {{ $openingHoursLabel }} — dining window must end by midnight.
                                        </p>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="party_size" class="mb-1.5 block text-xs font-bold uppercase tracking-widest text-gray-500">How many guests?</label>
                                            <div class="flex items-center gap-2">
                                                <button type="button" class="flex size-10 items-center justify-center rounded-lg border border-gray-200 text-gray-700 hover:bg-slate-100" x-on:click="partySize = Math.max(1, partySize - 1); onPartySizeChange()">−</button>
                                                <input id="party_size" type="number" min="1" max="50" x-model.number="partySize" x-on:input="onPartySizeChange()" class="sg-input w-full text-center">
                                                <button type="button" class="flex size-10 items-center justify-center rounded-lg border border-gray-200 text-gray-700 hover:bg-slate-100" x-on:click="partySize = Math.min(50, partySize + 1); onPartySizeChange()">+</button>
                                            </div>
                                        </div>
                                        <div>
                                            <label for="reserved_at" class="mb-1.5 block text-xs font-bold uppercase tracking-widest text-gray-500">When?</label>
                                            <input id="reserved_at" type="datetime-local" required
                                                min="{{ now()->format('Y-m-d\TH:i') }}"
                                                x-model="reservedAt"
                                                x-on:change="scheduleAvailabilityRefresh()"
                                                class="sg-input w-full">
                                            <p class="mt-1 text-[10px] text-zinc-500">Choose 2:00 PM or later (last start ~10:00 PM for a 2-hour stay).</p>
                                        </div>
                                    </div>

                                    <div x-show="reservedAt && !withinOpeningHours" x-cloak class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-xs text-red-300">
                                        That time is outside opening hours ({{ $openingHoursLabel }}). Please pick another time.
                                    </div>

                                    <div x-show="reservedAt && withinOpeningHours && sameDayTooSoon()" x-cloak class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-xs text-red-300">
                                        Same-day reservations must be made at least 1 hour in advance. Please pick a later time or choose a future date.
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-widest text-gray-500">Area</label>
                                        <select x-model="selectedGroupId" x-on:change="onGroupChange()" class="sg-input w-full">
                                            <template x-for="group in groups" :key="group.id">
                                                <option x-bind:value="group.id" x-text="group.name"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div>
                                        <div class="mb-2 flex flex-wrap items-center gap-2 text-[10px] text-gray-500">
                                            <span class="font-bold uppercase tracking-widest text-gray-500">Tap your table</span>
                                            <span class="inline-flex items-center gap-1"><span class="size-2 rounded-full bg-emerald-500"></span> Fits</span>
                                            <span class="inline-flex items-center gap-1"><span class="size-2 rounded-full bg-amber-500"></span> Extra chairs</span>
                                            <span class="inline-flex items-center gap-1"><span class="size-2 rounded-full bg-red-500"></span> Occupied / Booked</span>
                                            <span class="inline-flex items-center gap-1"><span class="size-2 rounded-full bg-orange-500"></span> Selected</span>
                                            <span x-show="availabilityLoading" class="text-zinc-500">Checking…</span>
                                        </div>

                                        <template x-if="!reservedAt">
                                            <p class="mb-3 rounded-lg border border-dashed border-white/15 px-4 py-3 text-center text-xs text-zinc-500">
                                                Choose a date &amp; time first to see which tables are free.
                                            </p>
                                        </template>

                                        <template x-if="selectedGroup()?.map_image_url">
                                            <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-black">
                                                <img x-bind:src="selectedGroup()?.map_image_url" alt="Floor map" class="block w-full select-none">
                                                <template x-for="table in (selectedGroup()?.tables ?? []).filter(t => t.is_placed_on_map)" :key="'map-' + table.id">
                                                    <button type="button"
                                                        class="absolute flex size-9 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full text-xs font-bold text-white shadow-lg transition"
                                                        x-bind:class="[mapMarkerClass(table), isTableUnavailable(table.id) ? '' : 'hover:scale-110']"
                                                        x-bind:style="mapMarkerStyle(table)"
                                                        x-bind:disabled="isTableUnavailable(table.id)"
                                                        x-on:click="selectTable(table)"
                                                        x-bind:title="'Table ' + table.table_number + ' — ' + tableStatusLabel(table)"
                                                        x-text="table.table_number"></button>
                                                </template>
                                            </div>
                                        </template>

                                        <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
                                            <template x-for="table in (selectedGroup()?.tables ?? [])" :key="'grid-' + table.id">
                                                <button type="button" class="rounded-xl border p-3 text-left transition"
                                                    x-bind:class="tableCardClass(table)"
                                                    x-bind:disabled="isTableUnavailable(table.id)"
                                                    x-on:click="selectTable(table)">
                                                    <p class="text-sm font-bold text-gray-900">Table <span x-text="table.table_number"></span></p>
                                                    <p class="text-xs text-gray-500"><span x-text="table.chair_count"></span> chairs</p>
                                                    <p class="mt-1 text-[10px] font-semibold uppercase"
                                                        x-bind:class="{
                                                            'text-red-400': isTableUnavailable(table.id),
                                                            'text-emerald-400': !isTableUnavailable(table.id) && partyFitsTable(table),
                                                            'text-amber-400': !isTableUnavailable(table.id) && !partyFitsTable(table),
                                                        }"
                                                        x-text="tableStatusLabel(table)"></p>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <div x-show="needsAdditionalChairs()" x-cloak class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-4">
                                        <p class="text-xs font-bold uppercase tracking-widest text-amber-400">Extra chairs needed</p>
                                        <p class="mt-2 text-sm text-amber-100/90">
                                            You selected a <span x-text="selectedTable()?.chair_count"></span>-seat table for
                                            <span x-text="partySize"></span> guests. You can still book it —
                                            we'll add <strong x-text="additionalChairs"></strong>
                                            <span x-text="additionalChairs === 1 ? 'extra chair' : 'extra chairs'"></span>
                                            for your party.
                                        </p>
                                        <label for="additional_chairs" class="mb-1.5 mt-3 block text-[10px] font-bold uppercase tracking-widest text-amber-400/80">Confirm extra chairs</label>
                                        <select id="additional_chairs" x-model.number="additionalChairs" class="sg-input w-full">
                                            <template x-for="n in additionalChairOptions()" :key="'extra-' + n">
                                                <option x-bind:value="n" x-text="n + (n === 1 ? ' extra chair' : ' extra chairs')"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <template x-if="!selectedTableId">
                                        <p class="rounded-lg border border-dashed border-gray-200 px-4 py-3 text-center text-xs text-gray-500">
                                            <i class="fa-solid fa-hand-pointer mr-1" aria-hidden="true"></i>
                                            Select an available table above to continue
                                        </p>
                                    </template>
                                </div>

                                {{-- STEP 3: Pre-order --}}
                                <div x-show="step === 3" x-cloak class="space-y-4">
                                    <div class="rounded-xl border border-gray-200 bg-slate-50 p-4">
                                        <h2 class="flex items-center gap-2 text-base font-bold text-gray-900">
                                            <i class="fa-solid fa-utensils text-orange-600" aria-hidden="true"></i>
                                            Pre-order from menu
                                        </h2>
                                        <p class="mt-1 text-xs text-gray-500">Optional — pick dishes to have ready when you arrive.</p>
                                    </div>

                                    <template x-if="menuCategories.length > 0">
                                        <div class="space-y-3">
                                            <div class="relative">
                                                <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-500" aria-hidden="true"></i>
                                                <input type="search" x-model="menuSearch" placeholder="Search menu…" class="sg-input w-full pl-9">
                                            </div>

                                            <div class="flex gap-2 overflow-x-auto pb-1">
                                                <template x-for="tab in menuCategoryTabs()" :key="'tab-' + tab.id">
                                                    <button type="button"
                                                        class="shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold transition"
                                                        x-bind:class="String(activeMenuCategory) === String(tab.id) ? 'bg-orange-500 text-white' : 'border border-gray-200 text-gray-500 hover:bg-slate-50'"
                                                        x-on:click="activeMenuCategory = tab.id"
                                                        x-text="tab.name"></button>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="menuCategories.length === 0">
                                        <p class="rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm text-gray-500">Menu unavailable — you can still reserve and order when you arrive.</p>
                                    </template>

                                    <template x-if="menuCategories.length > 0 && filteredMenuCategories().length === 0">
                                        <p class="rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm text-gray-500">No items match your search.</p>
                                    </template>

                                    <div class="max-h-72 space-y-4 overflow-y-auto rounded-xl border border-gray-200 bg-black/20 p-3 sm:max-h-96">
                                        <template x-for="category in filteredMenuCategories()" :key="'cat-' + category.id">
                                            <div>
                                                <p class="mb-2 text-xs font-bold uppercase tracking-widest text-orange-600" x-text="category.name"></p>
                                                <div class="space-y-2">
                                                    <template x-for="item in category.items" :key="'item-' + item.id">
                                                        <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-2"
                                                            x-bind:class="itemQty(item.id) > 0 ? 'border-orange-500/40 bg-orange-500/10' : ''">
                                                            <img x-bind:src="item.image_url" x-bind:alt="item.name" class="size-11 shrink-0 rounded-lg object-cover">
                                                            <div class="min-w-0 flex-1">
                                                                <p class="truncate text-sm font-semibold text-gray-900" x-text="item.name"></p>
                                                                <p class="text-xs font-bold text-orange-600" x-text="item.price_label"></p>
                                                            </div>
                                                            <div class="flex shrink-0 items-center gap-1">
                                                                <button type="button" class="flex size-8 items-center justify-center rounded-lg border border-gray-200 text-gray-700 hover:bg-slate-100 disabled:opacity-30"
                                                                    x-on:click="removeMenuItem(item.id)" x-bind:disabled="itemQty(item.id) === 0">
                                                                    <i class="fa-solid fa-minus text-[10px]" aria-hidden="true"></i>
                                                                </button>
                                                                <span class="w-5 text-center text-sm font-bold text-gray-900" x-text="itemQty(item.id)"></span>
                                                                <button type="button" class="flex size-8 items-center justify-center rounded-lg bg-orange-500 text-white hover:bg-orange-400"
                                                                    x-on:click="addMenuItem(item)">
                                                                    <i class="fa-solid fa-plus text-[10px]" aria-hidden="true"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    <div>
                                        <label for="special_instructions" class="mb-1.5 block text-xs font-bold uppercase tracking-widest text-gray-500">Special instructions <span class="font-normal normal-case">(optional)</span></label>
                                        <textarea id="special_instructions" rows="2" x-model="specialInstructions" placeholder="Allergies, no onions, extra sauce…" class="sg-input w-full"></textarea>
                                    </div>

                                    <p class="text-center text-xs text-gray-500" x-show="!hasOrderItems()">
                                        No pre-order? Skip ahead — you'll only pay the ₱500 table deposit.
                                    </p>
                                </div>

                                {{-- STEP 4: Deposit --}}
                                <div x-show="step === 4" x-cloak class="space-y-4">
                                    <div class="rounded-xl border border-orange-500/30 bg-orange-500/10 p-4">
                                        <h2 class="flex items-center gap-2 text-base font-bold text-gray-900">
                                            <i class="fa-solid fa-wallet text-orange-600" aria-hidden="true"></i>
                                            Refundable deposit
                                        </h2>
                                        <p class="mt-2 text-xs leading-relaxed text-gray-700">
                                            A deposit secures your table. <strong class="text-orange-700">This amount is deducted from your final bill</strong> when you order — it's not an extra charge.
                                        </p>
                                    </div>

                                    <div class="rounded-xl border border-gray-200 bg-slate-50 p-4 text-sm">
                                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Deposit breakdown</p>
                                        <dl class="mt-3 space-y-2">
                                            <div class="flex justify-between text-gray-700">
                                                <dt>Table reservation</dt>
                                                <dd class="font-semibold text-gray-900" x-text="formatMoney(depositBreakdown().table)"></dd>
                                            </div>
                                            <div class="flex justify-between text-gray-700" x-show="depositBreakdown().preorder > 0">
                                                <dt>Pre-order deposit</dt>
                                                <dd class="font-semibold text-gray-900" x-text="formatMoney(depositBreakdown().preorder)"></dd>
                                            </div>
                                            <div class="flex justify-between border-t border-gray-200 pt-2 text-base font-bold text-orange-600">
                                                <dt>Total deposit due</dt>
                                                <dd x-text="formatMoney(depositBreakdown().total)"></dd>
                                            </div>
                                        </dl>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-xs font-bold uppercase tracking-widest text-gray-500">How will you pay the deposit?</label>
                                        <div class="space-y-2">
                                            @foreach (\App\Models\Order::PAYMENT_METHODS as $value => $label)
                                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 transition has-[:checked]:border-orange-500/50 has-[:checked]:bg-orange-500/10">
                                                    <input type="radio" name="deposit_payment_method" value="{{ $value }}" x-model="paymentMethod" class="text-orange-500">
                                                    <span class="text-sm text-gray-900">{{ $label }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div x-show="paymentMethod === 'cash'" x-cloak class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-700">
                                        <i class="fa-solid fa-store mr-1" aria-hidden="true"></i>
                                        Pay <span x-text="formatMoney(depositBreakdown().total)"></span> at the restaurant before your reserved time. Staff will deduct it from your bill.
                                    </div>

                                    <div x-show="paymentMethod === 'gcash' || paymentMethod === 'maya'" x-cloak class="rounded-xl border border-orange-500/30 bg-orange-500/10 p-4">
                                        <label for="deposit_payment_proof" class="mb-2 block text-xs font-bold uppercase tracking-widest text-orange-700">
                                            Payment screenshot
                                        </label>
                                        <p class="mb-3 text-xs text-gray-500">Upload your <span x-text="paymentMethod === 'gcash' ? 'GCash' : 'Maya'"></span> transfer screenshot for <span x-text="formatMoney(depositBreakdown().total)"></span>.</p>
                                        <input
                                            id="deposit_payment_proof"
                                            name="deposit_payment_proof"
                                            type="file"
                                            accept="image/*"
                                            class="block w-full text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-orange-500 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white"
                                            x-bind:required="paymentMethod === 'gcash' || paymentMethod === 'maya'"
                                            @change="onDepositProofSelected($event)"
                                        >
                                        <div x-show="scanning" class="mt-3 flex items-center gap-2 text-xs text-orange-700">
                                            <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Reading screenshot…
                                        </div>
                                        <p x-show="scanError" x-text="scanError" class="mt-2 text-xs text-red-700"></p>
                                        <div x-show="referenceNumber || amountPaidLabel" x-cloak class="mt-3 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-xs text-emerald-700">
                                            <p x-show="referenceNumber">Ref: <span class="font-semibold" x-text="referenceNumber"></span></p>
                                            <p x-show="amountPaidLabel">Paid: <span class="font-semibold" x-text="amountPaidLabel"></span></p>
                                            <p x-show="depositAmountMismatch()" class="mt-1 text-amber-700">Amount is less than the required deposit. Please verify.</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Navigation --}}
                                <div class="mt-6 flex items-center gap-3 border-t border-gray-200 pt-5">
                                    <button type="button" x-show="step > 1" x-on:click="prevStep()"
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-slate-50">
                                        <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i> Back
                                    </button>

                                    <button type="button" x-show="step < 4" x-on:click="nextStep()"
                                        class="ml-auto inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-orange-500 px-6 py-3 text-sm font-bold text-white shadow-sm hover:bg-orange-400 sm:flex-none">
                                        Continue
                                        <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                                    </button>

                                    <button type="submit" x-show="step === 4"
                                        class="ml-auto inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-orange-500 px-6 py-3 text-sm font-bold text-white shadow-sm hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-50 sm:flex-none"
                                        x-bind:disabled="!canSubmit()">
                                        <i class="fa-solid fa-calendar-check text-xs" aria-hidden="true"></i>
                                        Confirm &amp; pay deposit
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Sticky summary sidebar --}}
                <div class="lg:col-span-1">
                    <div class="space-y-4 lg:sticky lg:top-24">
                        <div class="rounded-2xl border border-gray-200 bg-white p-5 backdrop-blur-sm">
                            <h2 class="font-display text-base font-bold text-gray-900">Booking summary</h2>
                            <p class="mt-1 text-xs text-gray-500">Updates as you go</p>

                            <dl class="mt-4 space-y-3 text-sm">
                                <div class="flex justify-between gap-2 border-b border-gray-100 pb-3">
                                    <dt class="text-gray-500">Guest</dt>
                                    <dd class="text-right font-medium text-gray-900" x-text="bookingSummary().name || '—'"></dd>
                                </div>
                                <div class="flex justify-between gap-2 border-b border-gray-100 pb-3">
                                    <dt class="text-gray-500">When</dt>
                                    <dd class="text-right text-gray-900" x-text="bookingSummary().when || '—'"></dd>
                                </div>
                                <div class="flex justify-between gap-2 border-b border-gray-100 pb-3">
                                    <dt class="text-gray-500">Table</dt>
                                    <dd class="text-right text-gray-900">
                                        <span x-text="bookingSummary().table || '—'"></span>
                                        <span class="block text-xs text-gray-500" x-show="bookingSummary().area" x-text="bookingSummary().area"></span>
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-2 border-b border-gray-100 pb-3">
                                    <dt class="text-gray-500">Party</dt>
                                    <dd class="text-right text-gray-900">
                                        <span x-text="bookingSummary().guests + ' guests'"></span>
                                        <span class="block text-xs text-gray-500" x-show="bookingSummary().extras > 0" x-text="'+' + bookingSummary().extras + ' extra chairs'"></span>
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-2 border-b border-gray-100 pb-3">
                                    <dt class="text-gray-500">Deposit</dt>
                                    <dd class="text-right text-gray-900">
                                        <span x-text="formatMoney(bookingSummary().deposit.total)"></span>
                                        <span class="block text-[10px] text-emerald-600">Deducted from final bill</span>
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="text-gray-500">Pre-order</dt>
                                    <dd class="text-right text-gray-900">
                                        <template x-if="bookingSummary().orderCount > 0">
                                            <span>
                                                <span x-text="bookingSummary().orderCount + ' items'"></span>
                                                <span class="block text-xs font-bold text-orange-600" x-text="formatMoney(bookingSummary().orderTotal)"></span>
                                            </span>
                                        </template>
                                        <template x-if="bookingSummary().orderCount === 0">
                                            <span class="text-gray-500">None yet</span>
                                        </template>
                                    </dd>
                                </div>
                            </dl>

                            <template x-if="hasOrderItems()">
                                <ul class="mt-4 space-y-1 border-t border-gray-200 pt-4">
                                    <template x-for="line in orderLines()" :key="'sum-' + line.item.id">
                                        <li class="flex justify-between gap-2 text-xs text-gray-500">
                                            <span x-text="line.qty + '× ' + line.item.name"></span>
                                            <span x-text="formatMoney(line.lineTotal)"></span>
                                        </li>
                                    </template>
                                </ul>
                            </template>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-white p-5 backdrop-blur-sm">
                            <h2 class="font-display text-base font-bold text-gray-900">Your reservations</h2>
                            @if ($reservations->isEmpty())
                                <p class="mt-3 text-sm text-gray-500">No bookings yet.</p>
                            @else
                                <ul class="mt-3 max-h-64 space-y-2 overflow-y-auto">
                                    @foreach ($reservations as $reservation)
                                        @php
                                            $badge = match ($reservation->status) {
                                                \App\Models\Reservation::STATUS_CONFIRMED => 'text-emerald-600',
                                                \App\Models\Reservation::STATUS_CANCELLED => 'text-red-600',
                                                default => 'text-orange-600',
                                            };
                                        @endphp
                                        <li class="rounded-lg border border-gray-200 bg-slate-50 p-3">
                                            <div class="flex items-start justify-between gap-2">
                                                <div class="min-w-0">
                                                    <p class="truncate text-sm font-semibold text-gray-900">
                                                        {{ $reservation->diningTable?->label() ?? 'Table' }}
                                                    </p>
                                                    <p class="text-xs text-gray-500">{{ $reservation->reserved_at->format('M d · h:i A') }}</p>
                                                    <p class="text-[10px] text-emerald-600">Deposit {{ $reservation->formattedDepositTotal() }} · {{ $reservation->depositStatusLabel() }}</p>
                                                </div>
                                                <span class="shrink-0 text-[10px] font-bold uppercase {{ $badge }}">{{ $reservation->statusLabel() }}</span>
                                            </div>
                                            @if (in_array($reservation->status, [\App\Models\Reservation::STATUS_PENDING, \App\Models\Reservation::STATUS_CONFIRMED]))
                                                <form action="{{ route('reservations.cancel', $reservation) }}" method="post" class="mt-2">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="text-[10px] font-semibold text-red-600 hover:text-red-700">Cancel</button>
                                                </form>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
