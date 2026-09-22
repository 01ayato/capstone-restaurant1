@extends('layouts.portal')

@section('title', 'Leave Feedback — ' . $settings->name)

@section('content')
    <section class="px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl">
            <h1 class="font-display text-3xl font-bold text-gray-900 dark:text-white">Leave a Review</h1>
            <p class="mt-2 text-gray-500">How was your experience with us?</p>

            @if (session('success'))
                <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (! $canLeaveFeedback)
                <div class="mt-8 rounded-2xl border border-gray-200 bg-white p-10 text-center dark:border-white/10 dark:bg-white/5">
                    <i class="fa-solid fa-lock mb-3 text-2xl text-gray-400" aria-hidden="true"></i>
                    <p class="text-gray-500">Reviews are available after your first completed order or reservation.</p>
                    <a href="{{ route('menu.index') }}" class="mt-6 inline-flex rounded-xl bg-orange-500 px-6 py-3 text-sm font-semibold text-white">View Menu</a>
                </div>
            @else
                <form
                    action="{{ route('feedback.store') }}"
                    method="post"
                    class="mt-8 space-y-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-[0_4px_12px_rgba(15,23,42,.05)] sm:p-8 dark:border-white/10 dark:bg-white/5"
                    x-data="{ rating: {{ (int) old('rating', 0) }} }"
                >
                    @csrf

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
                        <label class="mb-2 block text-xs font-bold uppercase tracking-widest text-gray-500">Your rating</label>
                        <div class="flex gap-1" role="radiogroup" aria-label="Star rating">
                            <template x-for="star in [1, 2, 3, 4, 5]" :key="star">
                                <button
                                    type="button"
                                    class="text-3xl transition"
                                    x-bind:class="star <= rating ? 'text-amber-400' : 'text-gray-300'"
                                    x-on:click="rating = star"
                                    x-bind:aria-label="'Rate ' + star + ' stars'"
                                    role="radio"
                                    x-bind:aria-checked="star === rating"
                                >
                                    <i class="fa-solid fa-star" aria-hidden="true"></i>
                                </button>
                            </template>
                        </div>
                        <input type="hidden" name="rating" x-bind:value="rating">
                    </div>

                    @if ($completedOrders->isNotEmpty() || $completedReservations->isNotEmpty())
                        <div>
                            <label for="linked_visit" class="mb-2 block text-xs font-bold uppercase tracking-widest text-gray-500">Which visit is this about? (optional)</label>
                            <select id="linked_visit" name="linked_visit" class="sg-input">
                                <option value="">General feedback</option>
                                @foreach ($completedOrders as $order)
                                    <option value="order:{{ $order->id }}" @selected(old('linked_visit') === 'order:'.$order->id)>
                                        Order {{ $order->order_number }} — {{ $order->created_at->format('M d, Y') }}
                                    </option>
                                @endforeach
                                @foreach ($completedReservations as $reservation)
                                    <option value="reservation:{{ $reservation->id }}" @selected(old('linked_visit') === 'reservation:'.$reservation->id)>
                                        Reservation — {{ $reservation->reserved_at->format('M d, Y h:i A') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label for="comment" class="mb-2 block text-xs font-bold uppercase tracking-widest text-gray-500">Tell us more (optional)</label>
                        <textarea id="comment" name="comment" rows="4" maxlength="1000" class="sg-input" placeholder="What did you like? What can we improve?">{{ old('comment') }}</textarea>
                    </div>

                    <button type="submit" x-bind:disabled="rating === 0" x-bind:class="rating === 0 ? 'opacity-50 cursor-not-allowed' : ''" class="w-full rounded-xl bg-orange-500 px-6 py-3 text-sm font-semibold text-white transition hover:bg-orange-400">
                        Submit Feedback
                    </button>
                </form>
            @endif

            @if ($myFeedback->isNotEmpty())
                <div class="mt-12">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">My Reviews</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($myFeedback as $feedback)
                            @php
                                $statusColors = [
                                    'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                                    'approved' => 'bg-green-100 text-green-700 border-green-200',
                                    'rejected' => 'bg-red-100 text-red-700 border-red-200',
                                ];
                                $color = $statusColors[$feedback->status] ?? 'bg-slate-100 text-gray-600 border-gray-200';
                            @endphp
                            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-white/5">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-amber-500">
                                        {{ str_repeat('★', $feedback->rating) }}<span class="text-gray-300">{{ str_repeat('★', 5 - $feedback->rating) }}</span>
                                    </p>
                                    <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ $color }}">{{ $feedback->statusLabel() }}</span>
                                </div>
                                @if ($feedback->comment)
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $feedback->comment }}</p>
                                @endif
                                <p class="mt-2 text-xs text-gray-400">{{ $feedback->created_at->format('M d, Y h:i A') }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
