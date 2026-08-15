<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\RestaurantSetting;
use App\Services\CartService;
use App\Services\TableSessionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(CartService $cart): View|RedirectResponse
    {
        if ($cart->isEmpty()) {
            return view('portal.cart', [
                'cartItems' => collect(),
                'subtotal' => 0,
                'deliveryFee' => $cart->deliveryFee(),
                'total' => $cart->deliveryFee(),
                'settings' => RestaurantSetting::current(),
                'cartCount' => 0,
            ]);
        }

        return view('portal.cart', [
            'cartItems' => $cart->items(),
            'subtotal' => $cart->subtotal(),
            'deliveryFee' => $cart->deliveryFee(),
            'total' => $cart->total(),
            'settings' => RestaurantSetting::current(),
            'cartCount' => $cart->count(),
        ]);
    }

    public function add(Request $request, CartService $cart, TableSessionService $tableSession): RedirectResponse
    {
        if (! $tableSession->hasTable()) {
            return back()->with('warning', 'Please scan your table QR or enter your table number before ordering.');
        }

        $validated = $request->validate([
            'menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $item = MenuItem::query()->where('is_available', true)->findOrFail($validated['menu_item_id']);
        $cart->add($item->id, $validated['quantity'] ?? 1);

        return back()->with('success', "{$item->name} added to cart.");
    }

    public function update(Request $request, CartService $cart): RedirectResponse
    {
        $validated = $request->validate([
            'menu_item_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
            'instructions' => ['nullable', 'string', 'max:500'],
        ]);

        $cart->update(
            (int) $validated['menu_item_id'],
            (int) $validated['quantity'],
            $validated['instructions'] ?? '',
        );

        return redirect()->route('cart.index')->with('success', 'Cart updated.');
    }

    public function remove(Request $request, CartService $cart): RedirectResponse
    {
        $validated = $request->validate([
            'menu_item_id' => ['required', 'integer'],
        ]);

        $cart->remove((int) $validated['menu_item_id']);

        return redirect()->route('cart.index')->with('success', 'Item removed from cart.');
    }
}
