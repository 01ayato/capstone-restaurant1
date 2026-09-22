@extends('layouts.portal')

@section('title', 'Customer Reviews — ' . $settings->name)

@section('content')
    <section class="px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-orange-500">Testimonials</p>
                    <h1 class="portal-heading mt-2 font-display text-3xl font-bold sm:text-4xl">All Customer Reviews</h1>
                    <p class="portal-muted mt-2 max-w-xl">What our guests are saying about their experience.</p>
                </div>
                <a href="{{ route('landing') }}" class="inline-flex items-center gap-2 rounded-[10px] border border-[#E8E3DB] bg-white px-5 py-2.5 text-sm font-semibold text-[#0D2232] shadow-sm transition hover:bg-[#F3F0EB] dark:border-white/15 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">
                    <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i> Back to Home
                </a>
            </div>

            @if ($reviews->isEmpty())
                <div class="mt-12 rounded-2xl border border-gray-200 bg-white p-12 text-center dark:border-white/10 dark:bg-white/5">
                    <p class="portal-muted">No reviews yet.</p>
                </div>
            @else
                <div class="mt-10 grid gap-5 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
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
                <div class="mt-8">{{ $reviews->links() }}</div>
            @endif
        </div>
    </section>
@endsection
