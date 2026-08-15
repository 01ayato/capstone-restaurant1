<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt — {{ $order->order_number }}</title>
    <style>
        body { font-family: monospace; max-width: 320px; margin: 2rem auto; padding: 1rem; }
        h1 { font-size: 1rem; text-align: center; }
        .center { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        td { padding: 0.25rem 0; }
        .total { font-weight: bold; border-top: 1px dashed #000; padding-top: 0.5rem; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body onload="window.print()">
    <h1>Smoke n' Grill</h1>
    <p class="center">{{ $order->order_number }}</p>
    <p class="center">{{ $order->created_at->format('M d, Y h:i A') }}</p>
    <p>{{ $order->customer_name }}<br>{{ $order->contact_number }}</p>
    <table>
        @foreach ($order->items as $item)
            <tr>
                <td>{{ $item->item_name }} × {{ $item->quantity }}</td>
                <td style="text-align:right">{{ \App\Support\Peso::format($item->line_total) }}</td>
            </tr>
        @endforeach
        <tr><td>Subtotal</td><td style="text-align:right">{{ \App\Support\Peso::format($order->subtotal) }}</td></tr>
        @if ((float) $order->delivery_fee > 0)
            <tr><td>Additional fee</td><td style="text-align:right">{{ \App\Support\Peso::format($order->delivery_fee) }}</td></tr>
        @endif
        <tr class="total"><td>Total</td><td style="text-align:right">{{ $order->formattedTotal() }}</td></tr>
    </table>
    <p class="center" style="margin-top:1rem">Payment: {{ $order->paymentMethodLabel() }}</p>
    <p class="center">Thank you!</p>
</body>
</html>
