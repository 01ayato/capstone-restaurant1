@extends('layouts.portal')

@section('title', 'Menu — ' . $settings->name)

@section('content')
    <section class="px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            @include('portal.partials.table-seat-banner')

            <div class="text-center">
                <p class="text-xs font-bold uppercase tracking-widest text-orange-500">Full Menu</p>
                <h1 class="mt-2 font-display text-3xl font-bold text-gray-900 sm:text-4xl">Browse Our Menu</h1>
                <p class="mx-auto mt-3 max-w-xl text-gray-500">All prices in Philippine Peso (₱). Add items to your cart and checkout when ready.</p>
            </div>

            <div
                x-data="portalMenu"
                data-initial-category-id="{{ $categories->firstWhere(fn ($category) => $category->availableMenuItems->isNotEmpty())?->id ?? '' }}"
                class="mt-10"
            >
                {{-- Category tabs --}}
                <div class="sticky top-0 z-10 -mx-4 border-b border-[#E8E3DB] bg-white/90 px-4 py-3 backdrop-blur dark:border-white/10 dark:bg-[#0a1a28]/80 sm:mx-0 sm:rounded-xl sm:border sm:px-3">
                    <div class="flex flex-wrap items-center justify-center gap-2">
                        @foreach ($categories as $category)
                            @if ($category->availableMenuItems->isNotEmpty())
                                <button
                                    type="button"
                                    class="rounded-full px-4 py-2 text-sm font-semibold transition"
                                    x-bind:class="activeCategory === '{{ $category->id }}' ? 'bg-orange-500 text-white shadow-sm' : 'bg-[#F3F0EB] text-gray-600 hover:bg-orange-100 hover:text-orange-700 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10'"
                                    x-on:click="setCategory('{{ $category->id }}')"
                                >
                                    {{ $category->name }}
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- Filterable menu grid --}}
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($categories as $category)
                        @foreach ($category->availableMenuItems as $item)
                            <div x-show="activeCategory === '{{ $category->id }}'" x-transition.opacity.duration.200ms>
                                @include('portal.partials.menu-card', ['item' => $item])
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
