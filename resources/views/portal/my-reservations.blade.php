@extends('layouts.portal')

@section('title', 'My Reservation — ' . ($settings->name ?? "Smoke n' Grill"))

@section('content')
    <script type="application/json" id="portal-my-reservations-initial">@json(['reservations' => $reservationsPayload])</script>

    <section
        class="px-4 py-12 sm:px-6 lg:px-8"
        x-data="portalMyReservationsPage(JSON.parse(document.getElementById('portal-my-reservations-initial')?.textContent || '{}'))"
    >
        <div class="mx-auto max-w-4xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-orange-500">Reservations</p>
                    <h1 class="mt-2 font-display text-3xl font-bold text-gray-900 dark:text-white">My Reservation</h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-white/60">View, track, and cancel your table bookings. Tap a card to see full details.</p>
                </div>
                <a href="{{ route('reservations.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-5 py-2.5 text-sm font-bold text-zinc-950 shadow-lg shadow-orange-500/20 transition hover:from-orange-400 hover:to-amber-400">
                    <i class="fa-solid fa-calendar-plus text-xs" aria-hidden="true"></i>
                    New reservation
                </a>
            </div>

            @if ($reservations->isEmpty())
                <div class="portal-card mt-12 p-12 text-center">
                    <i class="fa-solid fa-calendar-xmark text-3xl text-gray-400 dark:text-white/40" aria-hidden="true"></i>
                    <p class="mt-4 text-gray-600 dark:text-white/60">You don't have any reservations yet.</p>
                    <a href="{{ route('reservations.index') }}" class="mt-6 inline-flex rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-6 py-3 text-sm font-semibold text-zinc-950">Reserve a table</a>
                </div>
            @else
                <div class="mt-8 space-y-4">
                    @foreach ($reservations as $reservation)
                        @php
                            $badge = match ($reservation->status) {
                                \App\Models\Reservation::STATUS_CONFIRMED => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
                                \App\Models\Reservation::STATUS_CANCELLED => 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300',
                                \App\Models\Reservation::STATUS_COMPLETED => 'border-zinc-200 bg-zinc-100 text-zinc-700 dark:border-zinc-500/30 dark:bg-zinc-500/10 dark:text-zinc-300',
                                default => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300',
                            };
                            $canCancel = in_array($reservation->status, [
                                \App\Models\Reservation::STATUS_PENDING,
                                \App\Models\Reservation::STATUS_CONFIRMED,
                            ], true);
                        @endphp
                        <article
                            role="button"
                            tabindex="0"
                            class="portal-card cursor-pointer p-5 transition hover:border-orange-300 dark:hover:border-orange-500/40 sm:p-6"
                            x-on:click="openView(reservations.find(r => r.id === {{ $reservation->id }}))"
                            x-on:keydown.enter="openView(reservations.find(r => r.id === {{ $reservation->id }}))"
                        >
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="portal-heading font-display text-lg font-bold">
                                            {{ $reservation->diningTable?->label() ?? 'Table' }}
                                        </h2>
                                        <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase {{ $badge }}">
                                            {{ $reservation->statusLabel() }}
                                        </span>
                                    </div>
                                    <p class="portal-muted mt-1 text-sm">
                                        {{ $reservation->diningTable?->tableGroup?->name }}
                                        · {{ $reservation->reserved_at->format('M d, Y · h:i A') }}
                                    </p>
                                    <p class="mt-2 text-sm text-gray-700 dark:text-white/80">
                                        {{ $reservation->party_size }} {{ \Illuminate\Support\Str::plural('guest', $reservation->party_size) }}
                                        @if ($reservation->additional_chairs > 0)
                                            · +{{ $reservation->additional_chairs }} {{ \Illuminate\Support\Str::plural('extra chair', $reservation->additional_chairs) }}
                                        @endif
                                    </p>
                                    <p class="mt-1 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                                        Deposit {{ $reservation->formattedDepositTotal() }} · {{ $reservation->depositStatusLabel() }}
                                    </p>
                                    @if ($reservation->notes)
                                        <p class="mt-3 line-clamp-2 rounded-lg border border-gray-200 bg-slate-50 px-3 py-2 text-xs text-gray-600 dark:border-white/10 dark:bg-black/20 dark:text-white/60">
                                            <i class="fa-solid fa-utensils mr-1 text-orange-500/80" aria-hidden="true"></i>
                                            {{ $reservation->notes }}
                                        </p>
                                    @endif
                                    <p class="mt-3 text-xs font-semibold text-orange-700 dark:text-orange-400">
                                        <i class="fa-solid fa-eye mr-1" aria-hidden="true"></i> Tap to view details
                                    </p>
                                </div>

                                <div class="flex shrink-0 flex-col items-stretch gap-2 sm:items-end" x-on:click.stop>
                                    @if ($canCancel)
                                        <form action="{{ route('reservations.cancel', $reservation) }}" method="post">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="w-full rounded-xl border border-red-200 px-4 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-50 dark:border-red-500/30 dark:text-red-300 dark:hover:bg-red-500/10 sm:w-auto">
                                                Cancel reservation
                                            </button>
                                        </form>
                                    @endif
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-gray-200 px-4 py-2 text-xs font-semibold text-gray-700 transition hover:bg-slate-50 dark:border-white/15 dark:text-white/80 dark:hover:bg-white/5"
                                        x-on:click="openView(reservations.find(r => r.id === {{ $reservation->id }}))"
                                    >
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i> View
                                    </button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- View dialog --}}
        <template x-teleport="body">
            <div
                x-show="viewOpen"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[200] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
                role="dialog"
                aria-modal="true"
                aria-labelledby="my-reservation-view-title"
                x-on:keydown.escape.window="closeView()"
                x-on:click.self="closeView()"
            >
                <div class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-white/10 dark:bg-[#112a3d]" x-on:click.stop>
                    <header class="flex shrink-0 items-start justify-between gap-3 border-b border-gray-200 px-5 py-4 dark:border-white/10">
                        <div>
                            <h2 id="my-reservation-view-title" class="font-display text-lg font-bold text-gray-900 dark:text-white">Reservation details</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-white/60" x-show="selected" x-text="selected?.table_label"></p>
                        </div>
                        <button type="button" class="rounded-lg p-1.5 text-gray-500 transition hover:bg-slate-100 hover:text-gray-900 dark:text-white/60 dark:hover:bg-white/5 dark:hover:text-white" x-on:click="closeView()">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                            <span class="sr-only">Close</span>
                        </button>
                    </header>

                    <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-5 py-5 text-sm" x-show="selected">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-gray-600 dark:text-white/60">Status</span>
                            <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase" x-bind:class="statusBadgeClass(selected?.status)" x-text="selected?.status_label"></span>
                        </div>

                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-600 dark:text-white/60">1. Your information</p>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <p class="text-xs text-gray-600 dark:text-white/60">Name</p>
                                    <p class="mt-0.5 font-medium text-gray-900 dark:text-white" x-text="selected?.customer_name"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600 dark:text-white/60">Contact</p>
                                    <p class="mt-0.5 text-gray-900 dark:text-white" x-text="selected?.contact_number"></p>
                                </div>
                                <div class="sm:col-span-2">
                                    <p class="text-xs text-gray-600 dark:text-white/60">Email</p>
                                    <p class="mt-0.5 text-gray-900 dark:text-white" x-text="selected?.email || '—'"></p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-600 dark:text-white/60">2. Table &amp; visit</p>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <p class="text-xs text-gray-600 dark:text-white/60">When</p>
                                    <p class="mt-0.5 text-gray-900 dark:text-white" x-text="selected?.reserved_at_display"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600 dark:text-white/60">Ends at</p>
                                    <p class="mt-0.5 text-gray-900 dark:text-white" x-text="selected?.ends_at_display || '—'"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600 dark:text-white/60">Table</p>
                                    <p class="mt-0.5 text-gray-900 dark:text-white">
                                        <span x-text="selected?.table_label"></span>
                                        <span class="block text-xs text-gray-600 dark:text-white/60" x-show="selected?.table_group" x-text="selected?.table_group"></span>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600 dark:text-white/60">Party</p>
                                    <p class="mt-0.5 text-gray-900 dark:text-white">
                                        <span x-text="selected?.party_size + ' guests'"></span>
                                        <span class="block text-xs text-amber-700 dark:text-amber-400" x-show="selected?.additional_chairs > 0" x-text="'+' + selected?.additional_chairs + ' extra chairs'"></span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-600 dark:text-white/60">3. Deposit</p>
                            <div class="mt-3 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3">
                                <div class="flex justify-between text-gray-700 dark:text-white/80">
                                    <span>Table deposit</span>
                                    <span x-text="formatMoney(selected?.table_deposit_amount)"></span>
                                </div>
                                <div class="mt-1 flex justify-between text-gray-700 dark:text-white/80" x-show="Number(selected?.preorder_deposit_amount) > 0">
                                    <span>Pre-order deposit</span>
                                    <span x-text="formatMoney(selected?.preorder_deposit_amount)"></span>
                                </div>
                                <div class="mt-2 flex justify-between border-t border-emerald-500/20 pt-2 font-bold text-emerald-700 dark:text-emerald-300">
                                    <span>Total deposit</span>
                                    <span x-text="formatMoney(selected?.deposit_total)"></span>
                                </div>
                                <p class="mt-2 text-xs text-emerald-700 dark:text-emerald-300">
                                    <span x-text="selected?.deposit_status_label"></span>
                                    <span x-show="selected?.deposit_payment_method"> · <span x-text="selected?.deposit_payment_method"></span></span>
                                </p>
                                <p class="mt-1 text-[10px] text-gray-600 dark:text-white/60">Deducted from your final bill when you order.</p>
                            </div>
                        </div>

                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-600 dark:text-white/60">4. Order to prepare</p>
                            <p class="mt-3 whitespace-pre-wrap rounded-xl border border-gray-200 bg-slate-50 px-4 py-3 text-gray-700 dark:border-white/10 dark:bg-black/30 dark:text-white/80" x-text="selected?.notes || 'No pre-order specified.'"></p>
                        </div>
                    </div>

                    <footer class="flex shrink-0 flex-wrap justify-end gap-2 border-t border-gray-200 px-5 py-4 dark:border-white/10">
                        <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-slate-50 dark:border-white/15 dark:text-white/80 dark:hover:bg-white/5" x-on:click="closeView()">Close</button>
                        <template x-if="selected?.can_cancel">
                            <form x-bind:action="selected?.cancel_url" method="post">
                                @csrf @method('PATCH')
                                <button type="submit" class="rounded-xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 dark:border-red-500/30 dark:text-red-300 dark:hover:bg-red-500/10">
                                    Cancel reservation
                                </button>
                            </form>
                        </template>
                    </footer>
                </div>
            </div>
        </template>
    </section>
@endsection
