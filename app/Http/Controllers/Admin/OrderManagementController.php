<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderManagementController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $orders = Order::query()
            ->with(['user', 'items'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'statuses' => Order::STATUS_LABELS,
            'currentStatus' => $status,
            'routes' => $this->orderRoutes(),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load('items');

        return view('admin.orders.show', [
            'order' => $order,
            'statuses' => Order::STATUS_LABELS,
            'routes' => $this->orderRoutes(),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Order::STATUSES)],
        ]);

        $order->update(['status' => $validated['status']]);

        return back()->with('success', 'Order status updated.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $order->delete();

        return back()->with('success', 'Order deleted.');
    }

    /**
     * @return array{index: string, show: string, status: string, destroy: string, receipt: string}
     */
    protected function orderRoutes(): array
    {
        if (request()->routeIs('page.smoke_n_grill.orders.*')) {
            return [
                'index' => 'page.smoke_n_grill.orders.index',
                'show' => 'page.smoke_n_grill.orders.show',
                'status' => 'page.smoke_n_grill.orders.status',
                'destroy' => 'page.smoke_n_grill.orders.destroy',
                'receipt' => 'page.smoke_n_grill.orders.receipt',
            ];
        }

        return [
            'index' => 'admin.orders.index',
            'show' => 'admin.orders.show',
            'status' => 'admin.orders.status',
            'destroy' => 'admin.orders.destroy',
            'receipt' => 'admin.orders.receipt',
        ];
    }

    public function receipt(Order $order): View
    {
        $order->load('items');

        return view('admin.orders.receipt', compact('order'));
    }
}
