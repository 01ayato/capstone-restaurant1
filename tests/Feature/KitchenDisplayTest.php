<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KitchenDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_orders_and_confirmed_reservation_preorders_are_queued_and_can_be_marked_ready(): void
    {
        config(['services.xendit.webhook_token' => 'test-callback-token']);

        $role = Role::query()->create([
            'slug' => Role::SLUG_KITCHEN,
            'name' => 'Kitchen',
        ]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $category = Category::query()->create([
            'name' => 'Grill',
            'slug' => 'grill',
        ]);
        $menuItem = MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Grilled chicken',
            'slug' => 'grilled-chicken',
            'price' => 250,
            'station' => 'kitchen',
        ]);

        $order = Order::query()->create([
            'order_number' => 'SG-KDS-1',
            'customer_name' => 'Online Customer',
            'contact_number' => '09123456789',
            'delivery_address' => 'Table 1',
            'payment_method' => 'xendit',
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'xendit_invoice_id' => 'inv-kds-order',
            'subtotal' => 250,
            'total' => 250,
            'status' => Order::STATUS_PENDING,
        ]);
        OrderItem::query()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'item_name' => $menuItem->name,
            'unit_price' => 250,
            'quantity' => 1,
            'line_total' => 250,
        ]);
        $this->withHeader('x-callback-token', 'test-callback-token')
            ->postJson(route('webhooks.xendit'), [
                'id' => 'inv-kds-order',
                'external_id' => "order-{$order->id}",
                'status' => 'PAID',
                'amount' => 250,
                'currency' => 'PHP',
            ])
            ->assertOk();

        $preorder = Reservation::query()->create([
            'customer_name' => 'Reservation Customer',
            'contact_number' => '09123456789',
            'party_size' => 2,
            'reserved_at' => now()->addDay(),
            'duration_minutes' => Reservation::DEFAULT_DURATION_MINUTES,
            'status' => Reservation::STATUS_PENDING,
            'deposit_payment_method' => 'xendit',
            'deposit_status' => Reservation::DEPOSIT_STATUS_PENDING,
            'xendit_invoice_id' => 'inv-kds-reservation',
            'preorder_items' => [['menu_item_id' => $menuItem->id, 'quantity' => 2]],
        ]);
        $this->withHeader('x-callback-token', 'test-callback-token')
            ->postJson(route('webhooks.xendit'), [
                'id' => 'inv-kds-reservation',
                'external_id' => "reservation-{$preorder->id}",
                'status' => 'PAID',
                'amount' => 500,
                'currency' => 'PHP',
            ])
            ->assertOk();
        $unconfirmedPreorder = Reservation::query()->create([
            'customer_name' => 'Unconfirmed Customer',
            'contact_number' => '09123456789',
            'party_size' => 2,
            'reserved_at' => now()->addDays(2),
            'duration_minutes' => Reservation::DEFAULT_DURATION_MINUTES,
            'status' => Reservation::STATUS_PENDING,
            'preorder_items' => [['menu_item_id' => $menuItem->id, 'quantity' => 1]],
        ]);

        $this->get('/kitchen')
            ->assertOk()
            ->assertSee('SG-KDS-1')
            ->assertSee('Reservation pre-order')
            ->assertSee('#'.$preorder->id)
            ->assertDontSee($unconfirmedPreorder->customer_name);

        $this->postJson(route('kitchen.orders.accept', $order), ['final_eta' => 15])
            ->assertOk();
        $this->postJson(route('kitchen.orders.ready', $order))
            ->assertOk();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_READY,
        ]);

        $this->postJson(route('kitchen.preorders.accept', $preorder))
            ->assertOk();
        $this->postJson(route('kitchen.preorders.ready', $preorder))
            ->assertOk();
        $this->assertDatabaseHas('reservations', [
            'id' => $preorder->id,
            'preorder_kitchen_status' => Reservation::PREORDER_KITCHEN_STATUS_READY,
        ]);
    }
}
