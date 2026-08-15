@extends('layouts.app')

@section('title', 'Order ' . $order->order_number)

@section('page-header')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a
                href="{{ route($routes['index']) }}"
                class="mb-2 inline-flex items-center gap-2 text-sm font-semibold text-zinc-600 transition hover:text-zinc-900"
            >
                <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
                Back to orders
            </a>
            <h1 class="text-xl font-bold tracking-tight text-zinc-900">{{ $order->order_number }}</h1>
            <p class="mt-1 text-sm text-zinc-500">{{ $order->created_at->format('M d, Y h:i A') }}</p>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="admin-card">
            <h2 class="text-sm font-semibold text-gray-900">Customer Details</h2>
            <dl class="mt-4 space-y-2 text-sm">
                <div><dt class="text-gray-500">Name</dt><dd class="font-medium">{{ $order->customer_name }}</dd></div>
                <div><dt class="text-gray-500">Contact</dt><dd>{{ $order->contact_number }}</dd></div>
                <div><dt class="text-gray-500">Table / Notes</dt><dd>{{ $order->delivery_address }}</dd></div>
                <div><dt class="text-gray-500">Payment</dt><dd>{{ $order->paymentMethodLabel() }}</dd></div>
                @if ($order->paymentProofUrl())
                    <div>
                        <dt class="text-gray-500">Payment proof</dt>
                        <dd class="mt-2">
                            <a href="{{ $order->paymentProofUrl() }}" target="_blank" class="inline-block">
                                <img src="{{ $order->paymentProofUrl() }}" alt="Payment proof" class="max-h-48 rounded-lg border border-gray-200">
                            </a>
                        </dd>
                    </div>
                @endif
                @if ($order->payment_reference)
                    <div>
                        <dt class="text-gray-500">Payment reference</dt>
                        <dd class="font-mono font-medium">{{ $order->payment_reference }}</dd>
                    </div>
                @endif
                @if ($order->payment_amount_paid)
                    <div>
                        <dt class="text-gray-500">Amount paid (OCR)</dt>
                        <dd class="font-medium">{{ \App\Support\Peso::format($order->payment_amount_paid) }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="admin-card">
            <h2 class="text-sm font-semibold text-gray-900">Update Status</h2>
            <form action="{{ route($routes['status'], $order) }}" method="post" class="mt-4">
                @csrf @method('PATCH')
                <select name="status" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="admin-btn-primary mt-3">Update Status</button>
            </form>
            <a href="{{ route($routes['receipt'], $order) }}" target="_blank" rel="noopener" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-indigo-600">
                <i class="fa-solid fa-print" aria-hidden="true"></i> Print Receipt
            </a>
        </div>
    </div>

    <div class="admin-card mt-6 overflow-x-auto">
        <h2 class="text-sm font-semibold text-gray-900">Order Items</h2>
        <table class="mt-4 min-w-[560px] text-sm">
            <thead><tr class="border-b text-left text-gray-500"><th class="pb-2">Item</th><th class="pb-2">Qty</th><th class="pb-2">Price</th><th class="pb-2 text-right">Total</th></tr></thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr class="border-b border-slate-100">
                        <td class="py-2">{{ $item->item_name }}</td>
                        <td class="py-2">{{ $item->quantity }}</td>
                        <td class="py-2">{{ \App\Support\Peso::format($item->unit_price) }}</td>
                        <td class="py-2 text-right">{{ \App\Support\Peso::format($item->line_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr><td colspan="3" class="pt-3 text-gray-500">Subtotal</td><td class="pt-3 text-right">{{ \App\Support\Peso::format($order->subtotal) }}</td></tr>
                @if ((float) $order->delivery_fee > 0)
                    <tr><td colspan="3" class="text-gray-500">Additional fee</td><td class="text-right">{{ \App\Support\Peso::format($order->delivery_fee) }}</td></tr>
                @endif
                <tr><td colspan="3" class="font-bold">Total</td><td class="text-right font-bold">{{ $order->formattedTotal() }}</td></tr>
            </tfoot>
        </table>
    </div>
@endsection
