<?php

namespace App\Http\Controllers\Modules\SmokeNGrill;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RestaurantSetting;
use App\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->with(['menuItems' => fn ($q) => $q->where('is_available', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Category $category) => $category->menuItems->isNotEmpty())
            ->values();

        $settings = RestaurantSetting::current();

        return view('modules.smoke_n_grill.pages.pos.index', [
            'categories' => $categories,
            'settings' => $settings,
            'paymentMethods' => Order::PAYMENT_METHODS,
            'posInitial' => [
                'categories' => $categories->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'items' => $category->menuItems->map(fn ($item) => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'price' => (float) $item->price,
                        'price_label' => $item->formattedPrice(),
                        'image' => $item->imageUrl(),
                        'stock_level' => $item->stock_level,
                        'reorder_level' => $item->reorder_level,
                        'tracks_inventory' => $item->tracksInventory(),
                        'is_low_stock' => $item->isLowStock(),
                    ])->values()->all(),
                ])->values()->all(),
                'storeUrl' => route('page.smoke_n_grill.pos.store'),
                'ordersUrl' => route('page.smoke_n_grill.orders.index'),
            ],
        ]);
    }

    public function store(Request $request, InventoryService $inventory): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:150'],
            'contact_number' => ['required', 'string', 'max:30'],
            'order_type' => ['required', 'in:dine_in,takeaway'],
            'payment_method' => ['required', 'in:cash,gcash,maya'],
            'special_instructions' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.instructions' => ['nullable', 'string', 'max:255'],
        ]);

        $deliveryAddress = match ($validated['order_type']) {
            'dine_in' => 'Dine-in',
            'takeaway' => 'Takeaway',
            default => 'Takeaway',
        };

        $deliveryFee = 0.0;

        $order = DB::transaction(function () use ($validated, $deliveryAddress, $deliveryFee, $inventory) {
            $subtotal = 0.0;
            $lineItems = [];

            foreach ($validated['items'] as $line) {
                $menuItem = MenuItem::query()
                    ->with('category')
                    ->where('id', $line['menu_item_id'])
                    ->where('is_available', true)
                    ->firstOrFail();

                $quantity = (int) $line['quantity'];
                $lineTotal = (float) $menuItem->price * $quantity;
                $subtotal += $lineTotal;

                $lineItems[] = [
                    'menu_item_id' => $menuItem->id,
                    'item_name' => $menuItem->name,
                    'unit_price' => $menuItem->price,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                    'special_instructions' => $line['instructions'] ?? null,
                ];
            }

            $inventory->deduct(array_map(fn (array $line) => [
                'menu_item_id' => $line['menu_item_id'],
                'quantity' => $line['quantity'],
            ], $lineItems));

            $order = Order::query()->create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => Auth::id(),
                'customer_name' => $validated['customer_name'],
                'contact_number' => $validated['contact_number'],
                'delivery_address' => $deliveryAddress,
                'payment_method' => $validated['payment_method'],
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total' => $subtotal + $deliveryFee,
                'status' => Order::STATUS_PENDING,
                'special_instructions' => $validated['special_instructions'] ?? null,
            ]);

            foreach ($lineItems as $lineItem) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    ...$lineItem,
                ]);
            }

            return $order;
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Order {$order->order_number} created.",
                'order_number' => $order->order_number,
                'order_id' => $order->id,
            ]);
        }

        return redirect()
            ->route('page.smoke_n_grill.pos.index')
            ->with('success', "Order {$order->order_number} created.");
    }
}
