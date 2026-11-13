<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Role;
use App\Models\Reservation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

class KitchenController extends Controller implements HasMiddleware
{
    /**
     * @return array<int, string>
     */
    public static function middleware(): array
    {
        return ['auth'];
    }

    public function index(Request $request): View
    {
        $this->authorizeKitchenAccess($request);

        $orders = Order::query()
            ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PREPARING])
            ->where('payment_status', Order::PAYMENT_STATUS_PAID)
            ->whereHas('items.menuItem', function ($query): void {
                $query->where('station', 'kitchen');
            })
            ->with([
                'items' => function ($query): void {
                    $query->whereHas('menuItem', function ($menuItemQuery): void {
                        $menuItemQuery->where('station', 'kitchen');
                    });
                },
                'items.menuItem',
            ])
            ->get();

        $orders
            ->where('status', Order::STATUS_PENDING)
            ->each(function (Order $order): void {
                $order->system_eta = (int) $order->items->max(
                    fn ($item): int => (int) ($item->menuItem?->base_prep_time ?? 0)
                ) + 5;
            });

        $reservations = Reservation::query()
            ->where('status', Reservation::STATUS_CONFIRMED)
            ->whereIn('preorder_kitchen_status', [
                Reservation::PREORDER_KITCHEN_STATUS_PENDING,
                Reservation::PREORDER_KITCHEN_STATUS_PREPARING,
            ])
            ->whereNotNull('preorder_items')
            ->with('diningTable')
            ->get();

        $menuItems = MenuItem::query()
            ->whereIn('id', $reservations
                ->flatMap(fn (Reservation $reservation): array => collect($reservation->preorder_items)
                    ->pluck('menu_item_id')
                    ->filter()
                    ->all())
                ->unique()
                ->all())
            ->where('station', 'kitchen')
            ->get()
            ->keyBy('id');

        $preorders = $reservations
            ->map(function (Reservation $reservation) use ($menuItems): Reservation {
                $reservation->kitchen_items = collect($reservation->preorder_items)
                    ->map(function (array $line) use ($menuItems): ?array {
                        $menuItem = $menuItems->get((int) ($line['menu_item_id'] ?? 0));
                        if ($menuItem === null) {
                            return null;
                        }

                        return [
                            'item_name' => $menuItem->name,
                            'quantity' => (int) ($line['quantity'] ?? 0),
                            'special_instructions' => null,
                        ];
                    })
                    ->filter()
                    ->values();

                return $reservation;
            })
            ->filter(fn (Reservation $reservation): bool => $reservation->kitchen_items->isNotEmpty())
            ->values();

        return view('kitchen.index', compact('orders', 'preorders'));
    }

    public function acceptOrder(Request $request, Order $order): JsonResponse
    {
        $this->authorizeKitchenAccess($request);
        abort_unless(
            $order->payment_status === Order::PAYMENT_STATUS_PAID
                && $order->status === Order::STATUS_PENDING,
            409,
            'Only paid pending orders can be accepted.'
        );

        $validated = $request->validate([
            'final_eta' => ['required', 'integer', 'min:1'],
        ]);

        $order->update([
            'status' => Order::STATUS_PREPARING,
            'final_eta' => $validated['final_eta'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order accepted and moved to preparation.',
        ]);
    }

    public function markOrderReady(Request $request, Order $order): JsonResponse
    {
        $this->authorizeKitchenAccess($request);
        abort_unless(
            $order->payment_status === Order::PAYMENT_STATUS_PAID
                && $order->status === Order::STATUS_PREPARING,
            409,
            'Only paid orders being prepared can be marked ready.'
        );

        $order->update(['status' => Order::STATUS_READY]);

        return response()->json([
            'success' => true,
            'message' => 'Order marked ready.',
        ]);
    }

    public function acceptPreorder(Request $request, Reservation $reservation): JsonResponse
    {
        $this->authorizeKitchenAccess($request);
        abort_unless(
            $reservation->status === Reservation::STATUS_CONFIRMED
                && $reservation->preorder_kitchen_status === Reservation::PREORDER_KITCHEN_STATUS_PENDING
                && is_array($reservation->preorder_items)
                && $reservation->preorder_items !== []
                && $this->hasKitchenPreorderItems($reservation),
            409,
            'Only confirmed pending pre-orders can be accepted.'
        );

        $reservation->update([
            'preorder_kitchen_status' => Reservation::PREORDER_KITCHEN_STATUS_PREPARING,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pre-order accepted and moved to preparation.',
        ]);
    }

    public function markPreorderReady(Request $request, Reservation $reservation): JsonResponse
    {
        $this->authorizeKitchenAccess($request);
        abort_unless(
            $reservation->status === Reservation::STATUS_CONFIRMED
                && $reservation->preorder_kitchen_status === Reservation::PREORDER_KITCHEN_STATUS_PREPARING,
            409,
            'Only confirmed pre-orders being prepared can be marked ready.'
        );

        $reservation->update([
            'preorder_kitchen_status' => Reservation::PREORDER_KITCHEN_STATUS_READY,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pre-order marked ready.',
        ]);
    }

    private function authorizeKitchenAccess(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user !== null && (
                $user->hasRoleSlug(Role::SLUG_ADMIN)
                || $user->hasRoleSlug(Role::SLUG_KITCHEN)
                || $user->hasRoleSlug(Role::SLUG_CASHIER)
            ),
            403,
            'Unauthorized access.'
        );
    }

    private function hasKitchenPreorderItems(Reservation $reservation): bool
    {
        return MenuItem::query()
            ->whereIn('id', collect($reservation->preorder_items)->pluck('menu_item_id')->all())
            ->where('station', 'kitchen')
            ->exists();
    }
}
