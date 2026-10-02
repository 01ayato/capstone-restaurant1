@extends('layouts.portal')

@section('title', $settings->name . ' — Where Great Food Meets Great Moments')

@section('content')
    <section class="relative overflow-hidden px-4 py-14 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
        <div class="portal-card mx-auto max-w-5xl px-6 py-12 text-center sm:px-12 sm:py-16 lg:py-20">
            <p class="mb-5 inline-flex items-center gap-2 rounded-full border border-orange-200 bg-orange-50 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-orange-600 dark:border-orange-400/25 dark:bg-orange-500/10 dark:text-orange-300">
                <i class="fa-solid fa-star" aria-hidden="true"></i> Premium Grilled Experience
            </p>
            <h1 class="portal-heading font-display text-4xl font-bold leading-tight tracking-tight sm:text-5xl lg:text-6xl">
                Where Great Food Meets
                <span class="text-orange-500">Great Moments</span>
            </h1>
            <p class="portal-muted mx-auto mt-6 max-w-2xl text-base leading-7 sm:text-lg">
                Enjoy premium grilled dishes and unforgettable dining experiences.
            </p>
            <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-4">
                <a href="{{ route('cart.index') }}" class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-[10px] bg-orange-500 px-8 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-orange-400 sm:w-auto">
                    <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i> Order Now
                </a>
                <a href="{{ route('menu.index') }}" class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-[10px] border border-[#E8E3DB] bg-white px-8 py-3 text-base font-semibold text-[#0D2232] shadow-sm transition hover:bg-[#F3F0EB] dark:border-white/15 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 sm:w-auto">
                    <i class="fa-solid fa-utensils" aria-hidden="true"></i> View Menu
                </a>
            </div>
        </div>
    </section>

    <section id="menu" class="border-t border-[#E8E3DB] bg-[#F3F0EB]/80 px-4 py-14 sm:px-6 sm:py-16 lg:px-8 dark:border-white/10 dark:bg-[#0a1a28]/70">
        <div class="mx-auto max-w-7xl">
            <div class="text-center">
                <p class="text-xs font-bold uppercase tracking-widest text-orange-500">Our Menu</p>
                <h2 class="portal-heading mt-2 font-display text-3xl font-bold sm:text-4xl">Signature Dishes</h2>
                <p class="portal-muted mx-auto mt-3 max-w-xl">A few guest favorites, handpicked from our full menu.</p>
            </div>

            @if ($signatureDishes->isNotEmpty())
                <div class="mt-12 grid gap-5 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4">
                    @foreach ($signatureDishes as $item)
                        @include('portal.partials.menu-card', ['item' => $item])
                    @endforeach
                </div>
            @endif

            <div class="mt-10 text-center">
                <a href="{{ route('menu.index') }}" class="inline-flex items-center gap-2 rounded-[10px] border border-[#E8E3DB] bg-white px-6 py-3 text-sm font-semibold text-[#0D2232] shadow-sm transition hover:bg-[#F3F0EB] dark:border-white/15 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">
                    <i class="fa-solid fa-utensils" aria-hidden="true"></i> View Full Menu
                </a>
            </div>
        </div>
    </section>

    @if ($reviews->isNotEmpty())
        <section id="reviews" class="border-t border-[#E8E3DB] px-4 py-14 sm:px-6 sm:py-16 lg:px-8 dark:border-white/10">
            <div class="mx-auto max-w-7xl">
                <div class="text-center">
                    <p class="text-xs font-bold uppercase tracking-widest text-orange-500">Testimonials</p>
                    <h2 class="portal-heading mt-2 font-display text-3xl font-bold sm:text-4xl">Customer Reviews</h2>
                    <p class="portal-muted mx-auto mt-3 max-w-xl">What our guests are saying about their experience.</p>
                </div>

                <div class="mt-12 grid gap-5 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
                    @foreach ($reviews as $review)
                        <div class="portal-card p-6">
                            <p class="text-amber-500">
                                {{ str_repeat('★', $review->rating) }}<span class="text-gray-300">{{ str_repeat('★', 5 - $review->rating) }}</span>
                            </p>
                            @if ($review->comment)
                                <p class="portal-muted mt-3 text-sm leading-6">"{{ $review->comment }}"</p>
                            @endif
                            <p class="portal-heading mt-4 text-sm font-semibold">
                                {{ $review->user?->name ? \App\Support\NameMask::mask($review->user->name) : 'Guest' }}
                            </p>
                        </div>
                    @endforeach
                </div>

                @if ($totalApprovedReviews > $reviews->count())
                    <div class="mt-10 text-center">
                        <a href="{{ route('reviews.index') }}" class="inline-flex items-center gap-2 rounded-[10px] border border-[#E8E3DB] bg-white px-6 py-3 text-sm font-semibold text-[#0D2232] shadow-sm transition hover:bg-[#F3F0EB] dark:border-white/15 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">
                            View All Reviews <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                        </a>
                    </div>
                @endif
            </div>
        </section>
    @endif
@endsection
