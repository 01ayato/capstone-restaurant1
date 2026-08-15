<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RestaurantSetting;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(CartService $cart): View
    {
        $orders = Order::query()
            ->where('user_id', Auth::id())
            ->with('items')
            ->latest()
            ->get();

        return view('portal.orders', [
            'orders' => $orders,
            'settings' => RestaurantSetting::current(),
            'cartCount' => $cart->count(),
        ]);
    }
}
