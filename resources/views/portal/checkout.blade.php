@extends('layouts.portal')

@section('title', 'Checkout — ' . $settings->name)

@section('content')
    <section class="px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl">
            @include('portal.partials.table-seat-banner')

            <h1 class="font-display text-3xl font-bold text-gray-900">Checkout</h1>
            <p class="mt-2 text-gray-500">Complete your order details below.</p>

            <div class="mt-8 grid gap-8 lg:grid-cols-5">
                <form
                    action="{{ route('checkout.store') }}"
                    method="post"
                    enctype="multipart/form-data"
                    class="space-y-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-[0_4px_12px_rgba(15,23,42,.05)] lg:col-span-3"
                    x-data="portalPaymentOcr(@js(route('checkout.scan-payment-proof')), @js(csrf_token()), @js((float) $amountDue))"
                    data-initial-payment-method="{{ old('payment_method', 'cash') }}"
                >
                    @csrf

                    <div>
                        <label for="customer_name" class="mb-2 block text-xs font-bold uppercase tracking-widest text-gray-500">Full Name</label>
                        <input id="customer_name" name="customer_name" type="text" required value="{{ old('customer_name', $user?->name) }}" class="sg-input">
                        @error('customer_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="contact_number" class="mb-2 block text-xs font-bold uppercase tracking-widest text-gray-500">Contact Number</label>
                        <input id="contact_number" name="contact_number" type="tel" required value="{{ old('contact_number', $user?->phone) }}" class="sg-input">
                        @error('contact_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-widest text-gray-500">Your table</label>
                        <input type="hidden" name="delivery_address" value="{{ $tableLocation }}">
                        <div class="sg-input flex items-center justify-between bg-slate-100 text-gray-900">
                            <span>{{ $tableLocation }}</span>
                            <span class="text-xs text-emerald-600">Confirmed</span>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-widest text-gray-500">Payment Method</label>
                        <div class="space-y-2">
                            @foreach (\App\Models\Order::PAYMENT_METHODS as $value => $label)
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 transition has-[:checked]:border-orange-500/50 has-[:checked]:bg-orange-500/10">
                                    <input type="radio" name="payment_method" value="{{ $value }}" x-model="paymentMethod" @checked(old('payment_method', 'cash') === $value) class="text-orange-500">
                                    <span class="text-sm text-gray-900">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('payment_method')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror

                        <div x-show="paymentMethod === 'gcash'" x-cloak class="mt-4 rounded-xl border border-orange-500/30 bg-orange-500/10 p-4">
                            <label for="payment_proof" class="mb-2 block text-xs font-bold uppercase tracking-widest text-orange-700">
                                Payment proof <span x-text="'(GCash screenshot)'"></span>
                            </label>
                            <p class="mb-3 text-xs text-gray-500">Upload a screenshot of your successful payment. We will read the reference number and amount automatically.</p>
                            <input
                                id="payment_proof"
                                name="payment_proof"
                                type="file"
                                accept="image/*"
                                class="block w-full text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-orange-500 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-orange-400"
                                x-bind:required="paymentMethod === 'gcash'"
                                @change="onProofSelected($event)"
                            >
                            @error('payment_proof')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

                            <div x-show="scanning" class="mt-3 flex items-center gap-2 text-xs text-orange-700">
                                <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                                <span>Reading payment screenshot…</span>
                            </div>

                            <div x-show="scanError" x-cloak class="mt-3 rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-xs text-red-700" x-text="scanError"></div>

                            <div x-show="referenceNumber || amountPaidLabel" x-cloak class="mt-3 space-y-2 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-3 text-sm text-emerald-700">
                                <p class="text-xs font-bold uppercase tracking-widest text-emerald-700">Detected from screenshot</p>
                                <p x-show="referenceNumber"><span class="text-emerald-600/80">Reference:</span> <span class="font-semibold" x-text="referenceNumber"></span></p>
                                <p x-show="amountPaidLabel"><span class="text-emerald-600/80">Amount paid:</span> <span class="font-semibold" x-text="amountPaidLabel"></span></p>
                                <p x-show="amountMismatch()" class="text-xs text-amber-700">Amount does not match your amount due ({{ \App\Support\Peso::format($amountDue) }}). Please verify before placing the order.</p>
                                <p x-show="scanMessage && !scanError" class="text-xs text-emerald-700/80" x-text="scanMessage"></p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="special_instructions" class="mb-2 block text-xs font-bold uppercase tracking-widest text-gray-500">Special Instructions (optional)</label>
                        <textarea id="special_instructions" name="special_instructions" rows="2" class="sg-input">{{ old('special_instructions') }}</textarea>
                    </div>

                    <button type="submit" class="w-full rounded-xl bg-orange-500 py-4 text-base font-bold text-white shadow-sm transition hover:bg-orange-400">
                        Place Order — {{ \App\Support\Peso::format($amountDue) }}
                    </button>
                </form>

                <div class="lg:col-span-2">
                    <div class="sticky top-24 rounded-2xl border border-gray-200 bg-white p-6 backdrop-blur-sm">
                        <h2 class="font-display text-lg font-semibold text-orange-600">Order Summary</h2>
                        <ul class="mt-4 space-y-3 text-sm">
                            @foreach ($cartItems as $line)
                                <li class="flex justify-between gap-2 text-gray-500">
                                    <span>{{ $line->item->name }} × {{ $line->quantity }}</span>
                                    <span class="text-gray-900">{{ \App\Support\Peso::format($line->line_total) }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <dl class="mt-4 space-y-2 border-t border-gray-200 pt-4 text-sm">
                            @if ($depositCredit > 0)
                                <div class="flex justify-between text-emerald-600">
                                    <dt>Reservation deposit credit</dt>
                                    <dd>−{{ \App\Support\Peso::format($depositCredit) }}</dd>
                                </div>
                                <p class="text-[10px] text-gray-500">Your refundable deposit from reservation #{{ $applicableReservation->id }} is applied to this bill.</p>
                            @endif
                            <div class="flex justify-between text-gray-500">
                                <dt>Subtotal</dt>
                                <dd>{{ \App\Support\Peso::format($subtotal) }}</dd>
                            </div>
                            <div class="flex justify-between text-base font-bold text-orange-600">
                                <dt>Amount due</dt>
                                <dd>{{ \App\Support\Peso::format($amountDue) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
