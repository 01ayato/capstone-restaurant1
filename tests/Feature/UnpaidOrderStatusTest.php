<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnpaidOrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpaid_order_cannot_be_moved_to_preparation_and_is_not_reported_as_preparing_to_customer(): void
    {
        $user = User::factory()->create([
            'role_id' => Role::query()->where('slug', Role::SLUG_ADMIN)->value('id'),
        ]);
        $this->actingAs($user);

        $order = Order::query()->create([
            'order_number' => 'SG-UNPAID-STATUS',
            'user_id' => $user->id,
            'customer_name' => 'Test Customer',
            'contact_number' => '09123456789',
            'delivery_address' => 'Table 1',
            'payment_method' => 'xendit',
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'subtotal' => 250,
            'total' => 250,
            'status' => Order::STATUS_PREPARING,
        ]);

        $this->patch(route('admin.orders.status', $order), [
            'status' => Order::STATUS_PREPARING,
        ])->assertSessionHasErrors('status');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_PREPARING,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
        ]);

        $this->get(route('orders.index'))
            ->assertOk()
            ->assertSeeText('Pending')
            ->assertDontSeeText('Preparing');
    }
}
