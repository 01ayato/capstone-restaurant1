<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Role;
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

        return view('kitchen.index', compact('orders'));
    }

    public function acceptOrder(Request $request, Order $order): JsonResponse
    {
        $this->authorizeKitchenAccess($request);

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

    private function authorizeKitchenAccess(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user !== null && ($user->hasRoleSlug(Role::SLUG_ADMIN) || $user->hasRoleSlug(Role::SLUG_KITCHEN) || $user->hasRoleSlug(Role::SLUG_CASHIER)    ),
            403,
            'Unauthorized access.'
        );
    }
}
