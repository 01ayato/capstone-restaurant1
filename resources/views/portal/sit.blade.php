@extends('layouts.portal')

@section('title', $table->label() . ' — ' . $settings->name)

@section('content')
    <section class="px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-lg">
            <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center backdrop-blur-sm">
                <p class="text-xs font-bold uppercase tracking-widest text-orange-500">{{ $table->tableGroup->name }}</p>
                <h1 class="mt-2 font-display text-3xl font-bold text-gray-900">{{ $table->label() }}</h1>
                <p class="mt-2 text-sm text-gray-500">{{ $table->chair_count }} chairs · Sit code: <span class="font-mono text-orange-600">{{ $table->sit_code }}</span></p>

                @if (session('success'))
                    <p class="mt-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-2 text-sm text-emerald-700">{{ session('success') }}</p>
                @endif

                @if ($table->isOccupied())
                    <div class="mt-6 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-4 text-left">
                        <p class="text-sm font-semibold text-amber-700">Seats in use</p>
                        <p class="mt-1 text-sm text-gray-700">
                            {{ $table->occupiedChairs() }} of {{ $table->chair_count }} chairs occupied
                        </p>
                    </div>

                    <a
                        href="{{ route('menu.index') }}"
                        class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-orange-500 px-6 py-3.5 text-sm font-bold text-white shadow-sm transition hover:bg-orange-400"
                    >
                        <i class="fa-solid fa-utensils" aria-hidden="true"></i>
                        Browse Menu
                    </a>

                    <form action="{{ route('sit.release', $table->sit_code) }}" method="post" class="mt-3">
                        @csrf
                        <button type="submit" class="w-full rounded-xl border border-gray-300 px-6 py-3 text-sm font-semibold text-gray-700 transition hover:bg-slate-50">
                            Clear seats
                        </button>
                    </form>
                @else
                    <p class="mt-6 text-sm text-gray-500">How many guests are sitting at this table?</p>

                    <form action="{{ route('sit.occupy', $table->sit_code) }}" method="post" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label for="occupied_chairs" class="mb-2 block text-left text-xs font-bold uppercase tracking-widest text-gray-500">Number of guests</label>
                            <select id="occupied_chairs" name="occupied_chairs" required class="sg-input w-full">
                                @for ($i = 1; $i <= $table->chair_count; $i++)
                                    <option value="{{ $i }}">{{ $i }} {{ $i === 1 ? 'guest' : 'guests' }}</option>
                                @endfor
                            </select>
                        </div>
                        <button type="submit" class="w-full rounded-xl bg-orange-500 px-6 py-3.5 text-sm font-bold text-white shadow-sm transition hover:bg-orange-400">
                            Confirm &amp; View Menu
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </section>
@endsection
