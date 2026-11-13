<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class XenditWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.xendit.webhook_token' => 'test-callback-token']);
    }

    public function test_webhook_rejects_missing_and_invalid_callback_tokens(): void
    {
        $this->postJson(route('webhooks.xendit'), [])
            ->assertUnauthorized();

        $this->withHeader('x-callback-token', 'incorrect-token')
            ->postJson(route('webhooks.xendit'), [])
            ->assertUnauthorized();
    }

    public function test_paid_order_webhook_marks_payment_paid_and_is_idempotent(): void
    {
        $order = Order::query()->create([
            'order_number' => 'SG-XENDIT-PAID',
            'customer_name' => 'Test Customer',
            'contact_number' => '09123456789',
            'delivery_address' => 'Table 1',
            'payment_method' => 'xendit',
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'xendit_invoice_id' => 'inv-order-paid',
            'subtotal' => 500,
            'total' => 500,
            'status' => Order::STATUS_PREPARING,
        ]);

        $response = $this->sendWebhook("order-{$order->id}", 'inv-order-paid', 'PAID');
        $response->assertOk();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'payment_reference' => 'inv-order-paid',
            'status' => Order::STATUS_PENDING,
        ]);

        $this->sendWebhook("order-{$order->id}", 'inv-order-paid', 'PAID')
            ->assertOk();

        $this->assertSame(1, Order::query()->where('payment_reference', 'inv-order-paid')->count());
    }

    public function test_expired_order_webhook_cancels_the_unpaid_order(): void
    {
        $reservation = Reservation::query()->create([
            'customer_name' => 'Test Customer',
            'contact_number' => '09123456789',
            'party_size' => 2,
            'table_deposit_amount' => 500,
            'preorder_deposit_amount' => 0,
            'deposit_total' => 500,
            'deposit_payment_method' => 'xendit',
            'deposit_status' => Reservation::DEPOSIT_STATUS_PAID,
            'reserved_at' => now()->addDay(),
            'duration_minutes' => Reservation::DEFAULT_DURATION_MINUTES,
            'status' => Reservation::STATUS_CONFIRMED,
        ]);
        $order = Order::query()->create([
            'order_number' => 'SG-XENDIT-EXPIRED',
            'reservation_id' => $reservation->id,
            'customer_name' => 'Test Customer',
            'contact_number' => '09123456789',
            'delivery_address' => 'Table 1',
            'payment_method' => 'xendit',
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'xendit_invoice_id' => 'inv-order-expired',
            'subtotal' => 500,
            'deposit_credit' => 200,
            'total' => 500,
            'status' => Order::STATUS_PENDING,
        ]);
        $reservation->update([
            'deposit_status' => Reservation::DEPOSIT_STATUS_APPLIED,
            'applied_order_id' => $order->id,
        ]);

        $this->sendWebhook("order-{$order->id}", 'inv-order-expired', 'EXPIRED')
            ->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => Order::PAYMENT_STATUS_EXPIRED,
            'status' => Order::STATUS_CANCELLED,
        ]);
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'deposit_status' => Reservation::DEPOSIT_STATUS_PAID,
            'applied_order_id' => null,
        ]);
    }

    public function test_paid_reservation_webhook_confirms_the_reservation(): void
    {
        $reservation = Reservation::query()->create([
            'customer_name' => 'Test Customer',
            'contact_number' => '09123456789',
            'party_size' => 2,
            'table_deposit_amount' => 500,
            'preorder_deposit_amount' => 0,
            'deposit_total' => 500,
            'deposit_payment_method' => 'xendit',
            'deposit_status' => Reservation::DEPOSIT_STATUS_PENDING,
            'reserved_at' => now()->addDay(),
            'duration_minutes' => Reservation::DEFAULT_DURATION_MINUTES,
            'status' => Reservation::STATUS_PENDING,
            'xendit_invoice_id' => 'inv-reservation-paid',
        ]);

        $this->sendWebhook("reservation-{$reservation->id}", 'inv-reservation-paid', 'PAID')
            ->assertOk();

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'deposit_status' => Reservation::DEPOSIT_STATUS_PAID,
            'status' => Reservation::STATUS_CONFIRMED,
            'deposit_payment_amount_paid' => 500,
        ]);
    }

    private function sendWebhook(string $externalId, string $invoiceId, string $status): TestResponse
    {
        return $this->withHeader('x-callback-token', 'test-callback-token')
            ->postJson(route('webhooks.xendit'), [
                'id' => $invoiceId,
                'external_id' => $externalId,
                'status' => $status,
                'amount' => 500,
                'currency' => 'PHP',
            ]);
    }
}
