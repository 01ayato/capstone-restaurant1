<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Reservation Slip — #{{ $reservation->id }}</title>
    <style>
        body {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            max-width: 320px;
            margin: 2rem auto;
            padding: 1rem;
            color: #111;
            font-size: 12px;
            line-height: 1.45;
        }
        h1 { font-size: 14px; text-align: center; margin: 0 0 0.25rem; }
        .center { text-align: center; }
        .banner {
            border: 2px solid #111;
            padding: 0.5rem 0.4rem;
            margin: 0.75rem 0 1rem;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-size: 11px;
        }
        .banner small {
            display: block;
            margin-top: 0.35rem;
            font-weight: 600;
            font-size: 10px;
            letter-spacing: 0;
            text-transform: none;
        }
        .meta { margin: 0.75rem 0; }
        .row { display: flex; justify-content: space-between; gap: 0.75rem; margin: 0.2rem 0; }
        .label { color: #444; }
        .section {
            border-top: 1px dashed #111;
            margin-top: 0.85rem;
            padding-top: 0.65rem;
        }
        .section-title {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.06em;
            margin-bottom: 0.4rem;
        }
        .notes {
            white-space: pre-wrap;
            word-break: break-word;
        }
        .footer-note {
            margin-top: 1.25rem;
            border-top: 1px dashed #111;
            padding-top: 0.75rem;
            text-align: center;
            font-size: 10px;
        }
        .no-print { text-align: center; margin-top: 1.5rem; }
        .no-print button {
            font: inherit;
            padding: 0.45rem 0.9rem;
            cursor: pointer;
            border: 1px solid #111;
            background: #111;
            color: #fff;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">
    <h1>{{ $settings->name ?? "Smoke n' Grill" }}</h1>
    @if (! empty($settings->address))
        <p class="center">{{ $settings->address }}</p>
    @endif
    @if (! empty($settings->phone))
        <p class="center">{{ $settings->phone }}</p>
    @endif

    <div class="banner">
        Unofficial reservation slip
        <small>This is NOT an official receipt / OR / sales invoice.</small>
    </div>

    <div class="meta">
        <div class="row"><span class="label">Slip #</span><span>R-{{ $reservation->id }}</span></div>
        <div class="row"><span class="label">Printed</span><span>{{ now()->format('M d, Y h:i A') }}</span></div>
        <div class="row"><span class="label">Status</span><span>{{ $reservation->statusLabel() }}</span></div>
    </div>

    <div class="section">
        <div class="section-title">Customer</div>
        <div>{{ $reservation->customer_name }}</div>
        <div>{{ $reservation->contact_number }}</div>
        @if ($reservation->email)
            <div>{{ $reservation->email }}</div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Reservation</div>
        <div class="row"><span class="label">Date & time</span><span>{{ $reservation->reserved_at->format('M d, Y h:i A') }}</span></div>
        @if ($reservation->endsAt())
            <div class="row"><span class="label">Ends at</span><span>{{ $reservation->endsAt()->format('M d, Y h:i A') }}</span></div>
        @endif
        <div class="row">
            <span class="label">Table</span>
            <span>{{ $reservation->diningTable?->label() ?? 'Not assigned' }}</span>
        </div>
        @if ($reservation->diningTable?->tableGroup)
            <div class="row"><span class="label">Area</span><span>{{ $reservation->diningTable->tableGroup->name }}</span></div>
        @endif
        <div class="row"><span class="label">Party</span><span>{{ $reservation->party_size }} guests</span></div>
        @if (($reservation->additional_chairs ?? 0) > 0)
            <div class="row"><span class="label">Extra chairs</span><span>+{{ $reservation->additional_chairs }}</span></div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Order to prepare</div>
        <div class="notes">{{ $reservation->notes ?: 'No order specified yet.' }}</div>
    </div>

    <div class="section">
        <div class="section-title">Refundable deposit (reference only)</div>
        <div class="row">
            <span class="label">Table deposit</span>
            <span>₱{{ number_format((float) ($reservation->table_deposit_amount ?? 500), 2) }}</span>
        </div>
        @if ((float) ($reservation->preorder_deposit_amount ?? 0) > 0)
            <div class="row">
                <span class="label">Pre-order deposit</span>
                <span>₱{{ number_format((float) $reservation->preorder_deposit_amount, 2) }}</span>
            </div>
        @endif
        <div class="row" style="font-weight:700;margin-top:0.35rem">
            <span>Total deposit</span>
            <span>₱{{ number_format((float) ($reservation->deposit_total ?? 0), 2) }}</span>
        </div>
        <div style="margin-top:0.4rem;font-size:10px">
            Deposit status: {{ $reservation->depositStatusLabel() }}
            @if ($reservation->deposit_payment_method)
                · via {{ $reservation->deposit_payment_method }}
            @endif
        </div>
    </div>

    <p class="footer-note">
        Acknowledgment for kitchen / counter use only.<br>
        Not valid as an official receipt, OR, or sales invoice.<br>
        Thank you!
    </p>

    <div class="no-print">
        <button type="button" onclick="window.print()">Print again</button>
    </div>
</body>
</html>
