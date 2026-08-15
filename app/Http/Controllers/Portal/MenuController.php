<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\RestaurantSetting;
use App\Services\CartService;
use Illuminate\Contracts\View\View;

class MenuController extends Controller
{
    public function index(CartService $cart): View
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->with(['availableMenuItems'])
            ->orderBy('sort_order')
            ->get();

        return view('portal.menu', [
            'categories' => $categories,
            'settings' => RestaurantSetting::current(),
            'cartCount' => $cart->count(),
        ]);
    }
}
