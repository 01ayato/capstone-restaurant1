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

            @foreach ($categories as $category)
                @if ($category->availableMenuItems->isNotEmpty())
                    <div class="mt-14" id="{{ $category->slug }}">
                        <h2 class="mb-6 font-display text-2xl font-semibold text-orange-600">{{ $category->name }}</h2>
                        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($category->availableMenuItems as $item)
                                @include('portal.partials.menu-card', ['item' => $item])
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </section>
@endsection
