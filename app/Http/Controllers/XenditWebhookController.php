<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Reservation;
use App\Services\InventoryService;
use App\Services\ReservationMailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class XenditWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        InventoryService $inventory,
        ReservationMailService $mailer
    ): JsonResponse {
        $expectedToken = config('services.xendit.webhook_token');
        $providedToken = $request->header('x-callback-token');

        if (
            ! is_string($expectedToken)
            || $expectedToken === ''
            || ! is_string($providedToken)
            || $providedToken === ''
            || ! hash_equals($expectedToken, $providedToken)
        ) {
            Log::warning('Rejected Xendit webhook with invalid callback token');

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->validate([
            'id' => ['required', 'string'],
            'external_id' => ['required', 'string'],
            'status' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string'],
        ]);
        $payload['status'] = strtoupper($payload['status']);

        Log::info('Xendit webhook received', ['payload' => $payload]);

        if (! in_array($payload['status'], ['PAID', 'EXPIRED'], true)) {
            return response()->json(['message' => 'Event ignored.']);
        }

        $transition = DB::transaction(function () use ($payload, $inventory): array {
            if (preg_match('/^order-(\d+)$/', $payload['external_id'], $matches) === 1) {
                $order = Order::query()->lockForUpdate()->findOrFail((int) $matches[1]);
                abort_unless($order->xendit_invoice_id === $payload['id'], 404);

                if ($order->payment_status !== Order::PAYMENT_STATUS_PENDING) {
                    return ['type' => 'ignored'];
                }

                if ($payload['status'] === 'PAID') {
                    $this->assertInvoiceAmount($payload, (float) $order->total);

                    $order->update([
                        'payment_status' => Order::PAYMENT_STATUS_PAID,
                        'payment_reference' => $payload['id'],
                        'payment_amount_paid' => $payload['amount'],
                    ]);

                    return ['type' => 'order_paid', 'order_id' => $order->id];
                }

                $order->update([
                    'payment_status' => Order::PAYMENT_STATUS_EXPIRED,
                    'status' => Order::STATUS_CANCELLED,
                ]);
                if ($order->reservation_id !== null && (float) $order->deposit_credit > 0) {
                    $reservation = Reservation::query()
                        ->lockForUpdate()
                        ->find($order->reservation_id);

                    if ($reservation?->applied_order_id === $order->id) {
                        $reservation->update([
                            'deposit_status' => Reservation::DEPOSIT_STATUS_PAID,
                            'applied_order_id' => null,
                        ]);
                    }
                }
                $inventory->restore($order->items()
                    ->whereNotNull('menu_item_id')
                    ->get(['menu_item_id', 'quantity'])
                    ->map(fn ($item): array => [
                        'menu_item_id' => (int) $item->menu_item_id,
                        'quantity' => (int) $item->quantity,
                    ])
                    ->all());

                return ['type' => 'order_expired', 'order_id' => $order->id];
            }

            if (preg_match('/^reservation-(\d+)$/', $payload['external_id'], $matches) === 1) {
                $reservation = Reservation::query()->lockForUpdate()->findOrFail((int) $matches[1]);
                abort_unless($reservation->xendit_invoice_id === $payload['id'], 404);

                if (
                    $reservation->deposit_status !== Reservation::DEPOSIT_STATUS_PENDING
                    || $reservation->status !== Reservation::STATUS_PENDING
                ) {
                    return ['type' => 'ignored'];
                }

                if ($payload['status'] === 'PAID') {
                    $this->assertInvoiceAmount($payload, (float) $reservation->deposit_total);

                    $reservation->update([
                        'deposit_status' => Reservation::DEPOSIT_STATUS_PAID,
                        'deposit_payment_reference' => $payload['id'],
                        'deposit_payment_amount_paid' => $payload['amount'],
                        'deposit_paid_at' => now(),
                        'status' => Reservation::STATUS_CONFIRMED,
                    ]);

                    return [
                        'type' => 'reservation_paid',
                        'reservation_id' => $reservation->id,
                        'previous_status' => Reservation::STATUS_PENDING,
                    ];
                }

                $reservation->update([
                    'deposit_status' => Reservation::DEPOSIT_STATUS_EXPIRED,
                    'status' => Reservation::STATUS_CANCELLED,
                ]);
                if (is_array($reservation->preorder_items) && $reservation->preorder_items !== []) {
                    $inventory->restore($reservation->preorder_items);
                }

                return [
                    'type' => 'reservation_expired',
                    'reservation_id' => $reservation->id,
                    'previous_status' => Reservation::STATUS_PENDING,
                ];
            }

            abort(404);
        });

        if (in_array($transition['type'], ['reservation_paid', 'reservation_expired'], true)) {
            $reservation = Reservation::query()
                ->with(['diningTable.tableGroup', 'user'])
                ->findOrFail($transition['reservation_id']);
            $mailer->sendStatusUpdate($reservation, $transition['previous_status']);
        }

        Log::info('Xendit webhook processed', [
            'external_id' => $payload['external_id'],
            'invoice_id' => $payload['id'],
            'status' => $payload['status'],
            'result' => $transition['type'],
        ]);

        return response()->json(['message' => 'Webhook processed.']);
    }

    /**
     * @param  array{id: string, external_id: string, status: string, amount: numeric-string|int|float, currency: string}  $payload
     */
    private function assertInvoiceAmount(array $payload, float $expectedAmount): void
    {
        abort_unless($payload['currency'] === 'PHP', 422, 'Unexpected invoice currency.');
        abort_unless(abs((float) $payload['amount'] - $expectedAmount) < 0.01, 422, 'Invoice amount does not match.');
    }
}
