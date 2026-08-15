@extends('layouts.app')

@section('title', 'Reservations')

@section('page-header')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-600">Guest services</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Reservations</h1>
            <p class="mt-1 text-sm text-gray-500">Plan arrivals, tables, and pre-orders with confidence.</p>
        </div>
        <button
            type="button"
            class="admin-btn-primary shrink-0"
            onclick="window.dispatchEvent(new CustomEvent('admin-reservations-open-create'))"
        >
            New Reservation
        </button>
    </div>
@endsection

@section('content')
    <script type="application/json" id="admin-reservations-initial">@json($pageInitial)</script>

    <div
        x-data="adminReservationsPage(JSON.parse(document.getElementById('admin-reservations-initial')?.textContent || '{}'))"
        x-on:admin-reservations-open-create.window="openCreate()"
    >
        <div class="admin-table-shell overflow-x-auto">
            <table class="min-w-[900px] divide-y divide-gray-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Customer</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Date & Time</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Table</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Party</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($reservations as $reservation)
                        @php
                            $reservationStatusClass = match (strtolower($reservation->statusLabel())) {
                                'completed', 'confirmed' => 'admin-badge-completed',
                                'cancelled' => 'admin-badge-cancelled',
                                'preparing', 'seated' => 'admin-badge-preparing',
                                default => 'admin-badge-pending',
                            };
                        @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ $reservation->customer_name }}</p>
                                <p class="text-xs text-gray-500">{{ $reservation->contact_number }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $reservation->reserved_at->format('M d, Y h:i A') }}</td>
                            <td class="px-4 py-3">
                                @if ($reservation->diningTable)
                                    <span class="font-medium">{{ $reservation->diningTable->label() }}</span>
                                    <span class="block text-xs text-gray-500">{{ $reservation->diningTable->tableGroup?->name }}</span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                {{ $reservation->party_size }}
                                @if ($reservation->additional_chairs > 0)
                                    <span class="block text-xs text-gray-500">+{{ $reservation->additional_chairs }} extra</span>
                                @endif
                            </td>
                            <td class="px-4 py-3"><span class="{{ $reservationStatusClass }}">{{ $reservation->statusLabel() }}</span></td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex size-8 items-center justify-center rounded-[10px] border border-[#204865]/20 bg-[#204865]/5 text-[#16364D] transition hover:bg-[#204865]/10"
                                        title="View reservation"
                                        x-on:click="openView(reservations.find(r => r.id === {{ $reservation->id }}))"
                                    >
                                        <i class="fa-solid fa-eye text-xs" aria-hidden="true"></i>
                                        <span class="sr-only">View</span>
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex size-8 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700"
                                        title="Edit reservation"
                                        x-on:click="openEdit(reservations.find(r => r.id === {{ $reservation->id }}))"
                                    >
                                        <i class="fa-solid fa-pen text-xs" aria-hidden="true"></i>
                                        <span class="sr-only">Edit</span>
                                    </button>
                                    @if ($reservation->notificationEmail())
                                        <form action="{{ route('admin.reservations.notify', $reservation) }}" method="post" class="inline">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex size-8 items-center justify-center rounded-lg border border-sky-200 text-sky-700 transition hover:border-sky-300 hover:bg-sky-50"
                                                title="Send email to {{ $reservation->notificationEmail() }}"
                                            >
                                                <i class="fa-solid fa-envelope text-xs" aria-hidden="true"></i>
                                                <span class="sr-only">Send email</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500">No reservations yet. Create one to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $reservations->links() }}</div>

        {{-- Create dialog --}}
        <template x-teleport="body">
            <div
                x-show="createOpen"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[200] flex items-center justify-center bg-[#0D2232]/50 p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="create-reservation-title"
                x-on:keydown.escape.window="closeCreate()"
                x-on:click.self="closeCreate()"
            >
                <div class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl" x-on:click.stop>
                    <header class="flex shrink-0 items-start justify-between gap-3 border-b border-gray-200 px-6 py-4">
                        <div>
                            <h2 id="create-reservation-title" class="text-lg font-bold text-gray-900">New Reservation</h2>
                            <p class="mt-1 text-sm text-gray-500">Customer details, table, and prep notes</p>
                        </div>
                        <button type="button" class="rounded-lg p-1 text-gray-500 transition hover:bg-slate-100 hover:text-gray-700" x-on:click="closeCreate()">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                            <span class="sr-only">Close</span>
                        </button>
                    </header>

                    <form action="{{ route('admin.reservations.store') }}" method="post" class="flex min-h-0 flex-1 flex-col">
                        @csrf

                        <div class="min-h-0 flex-1 space-y-6 overflow-y-auto px-6 py-5">
                            @if ($errors->any())
                                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                    <ul class="list-inside list-disc space-y-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">1. Customer information</p>
                                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                    <input name="customer_name" placeholder="Customer name" value="{{ old('customer_name') }}" required class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                    <input name="contact_number" placeholder="Contact number" value="{{ old('contact_number') }}" required class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                    <input name="email" type="email" placeholder="Email" value="{{ old('email') }}" class="sm:col-span-2 rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                </div>
                            </div>

                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">2. Table &amp; reservation</p>
                                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                    <input name="party_size" type="number" min="1" placeholder="Party size" value="{{ old('party_size') }}" required class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                    <input name="additional_chairs" type="number" min="0" max="20" placeholder="Additional chairs" value="{{ old('additional_chairs', 0) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                    <input name="reserved_at" type="datetime-local" value="{{ old('reserved_at') }}" required class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                    <select name="dining_table_id" class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                        <option value="">No table (assign later)</option>
                                        @foreach ($groups as $group)
                                            <optgroup label="{{ $group->name }}">
                                                @foreach ($group->diningTables as $table)
                                                    <option value="{{ $table->id }}" @selected(old('dining_table_id') == $table->id)>
                                                        Table {{ $table->table_number }} · {{ $table->chair_count }} chairs
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">3. Order to prepare</p>
                                <textarea name="notes" rows="2" placeholder="Dishes or notes the customer wants ready" class="mt-3 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm">{{ old('notes') }}</textarea>
                                @if ($drinkItems->isNotEmpty())
                                    <div
                                        class="mt-4 rounded-lg border border-amber-200 bg-amber-50/50 p-4"
                                        x-data="{
                                            drinks: {{ json_encode($drinkItems->values()->map(fn ($drink, $index) => [
                                                'index' => $index,
                                                'id' => $drink->id,
                                                'name' => $drink->name,
                                                'stock_level' => $drink->stock_level,
                                                'tracks_inventory' => $drink->tracksInventory(),
                                                'qty' => (int) old('items.'.$index.'.quantity', 0),
                                            ])->values()) }},
                                            increase(drink) {
                                                if (drink.qty < 99) drink.qty += 1;
                                            },
                                            decrease(drink) {
                                                if (drink.qty > 0) drink.qty -= 1;
                                            },
                                        }"
                                    >
                                        <p class="text-xs font-semibold text-amber-900">Drinks (deducts stock)</p>
                                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                            <template x-for="drink in drinks" :key="drink.id">
                                                <div class="rounded-lg border border-zinc-200 bg-white p-3">
                                                    <p class="text-sm font-medium text-zinc-800" x-text="drink.name"></p>
                                                    <p class="mt-0.5 text-[11px] text-zinc-500" x-show="drink.tracks_inventory" x-text="'Stock: ' + drink.stock_level"></p>
                                                    <input type="hidden" x-bind:name="'items[' + drink.index + '][menu_item_id]'" x-bind:value="drink.id">
                                                    <input type="hidden" x-bind:name="'items[' + drink.index + '][quantity]'" x-bind:value="drink.qty">
                                                    <div class="mt-3 flex items-center gap-2">
                                                        <button
                                                            type="button"
                                                            class="inline-flex size-8 items-center justify-center rounded-lg border border-zinc-300 text-sm font-bold text-zinc-700 transition hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-40"
                                                            x-bind:disabled="drink.qty <= 0"
                                                            x-on:click="decrease(drink)"
                                                        >
                                                            −
                                                        </button>
                                                        <span class="min-w-8 text-center text-sm font-semibold text-zinc-900" x-text="drink.qty"></span>
                                                        <button
                                                            type="button"
                                                            class="inline-flex size-8 items-center justify-center rounded-lg border border-zinc-300 text-sm font-bold text-zinc-700 transition hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-40"
                                                            x-bind:disabled="drink.qty >= 99 || (drink.tracks_inventory && drink.qty >= drink.stock_level)"
                                                            x-on:click="increase(drink)"
                                                        >
                                                            +
                                                        </button>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <footer class="flex shrink-0 justify-end gap-2 border-t border-gray-200 px-6 py-4">
                            <button type="button" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-slate-50" x-on:click="closeCreate()">Cancel</button>
                            <button type="submit" class="admin-btn-primary">Add Reservation</button>
                        </footer>
                    </form>
                </div>
            </div>
        </template>

        {{-- View dialog --}}
        <template x-teleport="body">
            <div
                x-show="viewOpen"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[200] flex items-center justify-center bg-[#0D2232]/50 p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="view-reservation-title"
                x-on:keydown.escape.window="closeView()"
                x-on:click.self="closeView()"
            >
                <div class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xl" x-on:click.stop>
                    <header class="flex shrink-0 items-start justify-between gap-3 border-b border-zinc-200 px-6 py-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 id="view-reservation-title" class="text-lg font-bold text-zinc-900">Reservation details</h2>
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                    x-bind:class="statusBadgeClass(selected?.status)"
                                    x-text="selected?.status_label"
                                ></span>
                            </div>
                            <p class="mt-1 truncate text-sm text-zinc-500" x-show="selected" x-text="selected?.customer_name"></p>
                        </div>
                        <button type="button" class="shrink-0 rounded-lg p-1 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-800" x-on:click="closeView()">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                            <span class="sr-only">Close</span>
                        </button>
                    </header>

                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-5 text-sm" x-show="selected">
                        <section>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">1. Customer information</p>
                            <div class="mt-2 grid gap-3 rounded-lg border border-zinc-200 bg-zinc-50/80 px-4 py-3 sm:grid-cols-2">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">Customer</p>
                                    <p class="mt-0.5 font-medium text-zinc-900" x-text="selected?.customer_name"></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">Contact</p>
                                    <p class="mt-0.5 text-zinc-900" x-text="selected?.contact_number"></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">Email</p>
                                    <p class="mt-0.5 text-zinc-900" x-text="selected?.email || '—'"></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">Account</p>
                                    <p class="mt-0.5 text-zinc-900" x-text="selected?.user_name || 'Walk-in / staff entry'"></p>
                                </div>
                            </div>
                        </section>

                        <section>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">2. Table &amp; reservation</p>
                            <div class="mt-2 grid gap-3 rounded-lg border border-zinc-200 bg-zinc-50/80 px-4 py-3 sm:grid-cols-2">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">Date &amp; time</p>
                                    <p class="mt-0.5 text-zinc-900" x-text="selected?.reserved_at_display"></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">Ends at</p>
                                    <p class="mt-0.5 text-zinc-900" x-text="selected?.ends_at_display"></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">Table</p>
                                    <p class="mt-0.5 text-zinc-900">
                                        <span x-text="selected?.table_label || 'Not assigned'"></span>
                                        <span class="block text-xs text-gray-500" x-show="selected?.table_group" x-text="selected?.table_group"></span>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">Party</p>
                                    <p class="mt-0.5 text-zinc-900">
                                        <span x-text="selected?.party_size + ' guests'"></span>
                                        <span class="block text-xs text-gray-500" x-show="selected?.additional_chairs > 0" x-text="'+' + selected?.additional_chairs + ' extra chairs'"></span>
                                    </p>
                                </div>
                            </div>
                        </section>

                        <section>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">3. Order to prepare</p>
                            <div class="mt-2 whitespace-pre-wrap rounded-lg border border-zinc-200 bg-zinc-50/80 px-4 py-3 text-zinc-700" x-text="selected?.notes || 'No order specified yet.'"></div>
                        </section>

                        <section>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">4. Refundable deposit</p>
                            <div class="mt-2 rounded-lg border border-zinc-200 bg-zinc-50/80 px-4 py-3">
                                <div class="flex justify-between gap-3">
                                    <span class="text-zinc-600">Table deposit</span>
                                    <span class="font-medium text-zinc-900" x-text="'₱' + Number(selected?.table_deposit_amount || 500).toFixed(2)"></span>
                                </div>
                                <div class="mt-1.5 flex justify-between gap-3" x-show="Number(selected?.preorder_deposit_amount) > 0">
                                    <span class="text-zinc-600">Pre-order deposit</span>
                                    <span class="font-medium text-zinc-900" x-text="'₱' + Number(selected?.preorder_deposit_amount).toFixed(2)"></span>
                                </div>
                                <div class="mt-2 flex justify-between gap-3 border-t border-zinc-200 pt-2 font-bold text-zinc-900">
                                    <span>Total deposit</span>
                                    <span x-text="'₱' + Number(selected?.deposit_total || 0).toFixed(2)"></span>
                                </div>
                                <p class="mt-2 text-xs text-emerald-700">Deducted from customer's final bill when they order.</p>
                                <p class="mt-1 text-xs text-gray-500">
                                    Status: <span class="font-semibold" x-text="selected?.deposit_status_label"></span>
                                    <span x-show="selected?.deposit_payment_method"> · via <span x-text="selected?.deposit_payment_method"></span></span>
                                </p>
                            </div>
                        </section>
                    </div>

                    <footer class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-zinc-200 bg-white px-6 py-4">
                        <button type="button" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50" x-on:click="closeView()">Close</button>
                        <form
                            x-show="selected?.can_email"
                            x-cloak
                            x-bind:action="selected?.notify_url"
                            method="post"
                            class="inline"
                        >
                            @csrf
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 rounded-lg border border-sky-200 px-4 py-2 text-sm font-semibold text-sky-800 hover:bg-sky-50"
                            >
                                <i class="fa-solid fa-envelope text-xs" aria-hidden="true"></i>
                                Send email
                            </button>
                        </form>
                        <a
                            x-show="selected?.status === 'completed'"
                            x-cloak
                            x-bind:href="selected?.receipt_url"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-50"
                        >
                            <i class="fa-solid fa-print text-xs" aria-hidden="true"></i>
                            Print slip
                        </a>
                        <button type="button" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800" x-on:click="closeView(); openEdit(selected)">Edit</button>
                    </footer>
                </div>
            </div>
        </template>

        {{-- Edit dialog --}}
        <template x-teleport="body">
            <div
                x-show="editOpen"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[200] flex items-center justify-center bg-[#0D2232]/50 p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="edit-reservation-title"
                x-on:keydown.escape.window="closeEdit()"
                x-on:click.self="closeEdit()"
            >
                <div class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl" x-on:click.stop>
                    <header class="shrink-0 border-b border-gray-200 px-6 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 id="edit-reservation-title" class="text-lg font-bold text-gray-900">Edit reservation</h2>
                                <p class="mt-1 text-sm text-gray-500" x-show="selected" x-text="selected?.customer_name"></p>
                            </div>
                            <button type="button" class="rounded-lg p-1 text-gray-500 transition hover:bg-slate-100 hover:text-gray-700" x-on:click="closeEdit()">
                                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                <span class="sr-only">Close</span>
                            </button>
                        </div>
                    </header>

                    <form x-bind:action="editDraft.update_url" method="post" class="flex min-h-0 flex-1 flex-col">
                        @csrf
                        @method('PUT')

                        <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">1. Customer information</p>
                                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-600">Customer name</label>
                                        <input type="text" name="customer_name" x-model="editDraft.customer_name" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-600">Contact number</label>
                                        <input type="text" name="contact_number" x-model="editDraft.contact_number" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="mb-1 block text-xs font-semibold text-gray-600">Email</label>
                                        <input type="email" name="email" x-model="editDraft.email" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">2. Table &amp; reservation</p>
                                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-600">Status</label>
                                        <select name="status" x-model="editDraft.status" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                            <template x-for="(label, value) in statuses" :key="'status-' + value">
                                                <option x-bind:value="value" x-text="label"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-600">Date & time</label>
                                        <input type="datetime-local" name="reserved_at" x-model="editDraft.reserved_at" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-600">Table</label>
                                        <select name="dining_table_id" x-model="editDraft.dining_table_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                            <option value="">No table (assign later)</option>
                                            <template x-for="group in groups" :key="'edit-group-' + group.id">
                                                <optgroup x-bind:label="group.name">
                                                    <template x-for="table in group.tables" :key="'edit-table-' + table.id">
                                                        <option x-bind:value="table.id" x-text="'Table ' + table.table_number + ' · ' + table.chair_count + ' chairs'"></option>
                                                    </template>
                                                </optgroup>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-600">Party size</label>
                                        <input type="number" name="party_size" min="1" max="50" x-model="editDraft.party_size" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-600">Additional chairs</label>
                                        <input type="number" name="additional_chairs" min="0" max="20" x-model="editDraft.additional_chairs" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">3. Order to prepare</p>
                                <textarea name="notes" rows="3" x-model="editDraft.notes" placeholder="Dishes or drinks the customer wants ready" class="mt-3 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"></textarea>
                            </div>
                        </div>

                        <footer class="flex shrink-0 justify-end gap-2 border-t border-gray-200 px-6 py-4">
                            <button type="button" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-slate-50" x-on:click="closeEdit()">Cancel</button>
                            <button type="submit" class="rounded-lg bg-[#0D2232] px-4 py-2 text-sm font-semibold text-white hover:bg-[#16364D]">Save changes</button>
                        </footer>
                    </form>
                </div>
            </div>
        </template>
    </div>
@endsection
