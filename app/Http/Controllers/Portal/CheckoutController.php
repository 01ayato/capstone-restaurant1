<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\ReservationController as PortalReservationController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Reservation;
use App\Models\RestaurantSetting;
use App\Services\CartService;
use App\Services\InventoryService;
use App\Services\TableSessionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CheckoutController extends Controller
{
    public function show(CartService $cart, TableSessionService $tableSession): View|RedirectResponse
    {
        if ($cart->isEmpty()) {
            return redirect()->route('menu.index')->with('warning', 'Your cart is empty.');
        }

        if (! $tableSession->hasTable()) {
            return redirect()
                ->route('menu.index')
                ->with('warning', 'Please select your table before checkout.');
        }

        $user = Auth::user();
        $table = $tableSession->currentTable();
        $applicableReservation = PortalReservationController::findApplicableDepositForUser(
            (int) $user->id,
            $table?->id
        );
        $depositCredit = $applicableReservation ? (float) $applicableReservation->deposit_total : 0.0;
        $cartTotal = $cart->total();
        $amountDue = max(0, $cartTotal - $depositCredit);

        return view('portal.checkout', [
            'cartItems' => $cart->items(),
            'subtotal' => $cart->subtotal(),
            'deliveryFee' => $cart->deliveryFee(),
            'total' => $cartTotal,
            'depositCredit' => $depositCredit,
            'amountDue' => $amountDue,
            'applicableReservation' => $applicableReservation,
            'settings' => RestaurantSetting::current(),
            'cartCount' => $cart->count(),
            'user' => $user,
            'tableLocation' => $tableSession->locationLabel(),
        ]);
    }

    public function store(Request $request, CartService $cart, TableSessionService $tableSession, InventoryService $inventory): RedirectResponse
    {
        if ($cart->isEmpty()) {
            return redirect()->route('menu.index')->with('warning', 'Your cart is empty.');
        }

        if (! $tableSession->hasTable()) {
            return redirect()
                ->route('menu.index')
                ->with('warning', 'Please select your table before placing an order.');
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:150'],
            'contact_number' => ['required', 'string', 'max:30'],
            'payment_method' => ['required', 'in:cash,xendit'],
            'special_instructions' => ['nullable', 'string', 'max:500'],
        ]);

        $deliveryAddress = $tableSession->locationLabel();
        $table = $tableSession->currentTable();
        $applicableReservation = PortalReservationController::findApplicableDepositForUser(
            (int) Auth::id(),
            $table?->id
        );
        $depositCredit = $applicableReservation ? (float) $applicableReservation->deposit_total : 0.0;
        $cartTotal = $cart->total();
        $amountDue = max(0, $cartTotal - $depositCredit);
        $orderNumber = Order::generateOrderNumber();

        $invoiceUrl = null;
        if ($validated['payment_method'] === 'xendit' && $amountDue > 0) {
            $apiKey = config('services.xendit.secret_key');
            if (! is_string($apiKey) || $apiKey === '') {
                return back()
                    ->withInput()
                    ->withErrors(['payment_method' => 'Online payment is not configured. Please choose another payment method.']);
            }

            try {
                $response = Http::withBasicAuth($apiKey, '')
                    ->acceptJson()
                    ->post('https://api.xendit.co/v2/invoices', [
                        'external_id' => $orderNumber,
                        'amount' => round($amountDue, 2),
                        'description' => 'Smoke n Grill Order',
                        'success_redirect_url' => route('orders.index'),
                        'failure_redirect_url' => route('orders.index'),
                    ]);
            } catch (\Illuminate\Http\Client\ConnectionException $exception) {
                report($exception);

                return back()
                    ->withInput()
                    ->withErrors(['payment_method' => 'Could not connect to the online payment service. Please try again.']);
            }

            $invoiceUrl = $response->json('invoice_url');
            if (! $response->successful() || ! is_string($invoiceUrl) || $invoiceUrl === '') {
                return back()
                    ->withInput()
                    ->withErrors(['payment_method' => 'Could not create an online payment link. Please try again.']);
            }
        }

        $order = DB::transaction(function () use ($cart, $validated, $deliveryAddress, $applicableReservation, $depositCredit, $amountDue, $inventory, $orderNumber) {
            $cartItems = $cart->items();
            $kitchenItems = $cartItems->filter(fn ($line): bool => $line->item->station === 'kitchen');
            $systemEta = $kitchenItems->isEmpty()
                ? null
                : (int) $kitchenItems->max(fn ($line): int => (int) $line->item->base_prep_time) + 5;

            $inventoryLines = [];
            foreach ($cartItems as $line) {
                $inventoryLines[] = [
                    'menu_item_id' => $line->item->id,
                    'quantity' => $line->quantity,
                ];
            }
            $inventory->deduct($inventoryLines);

            $order = Order::query()->create([
                'order_number' => $orderNumber,
                'user_id' => Auth::id(),
                'reservation_id' => $applicableReservation?->id,
                'customer_name' => $validated['customer_name'],
                'contact_number' => $validated['contact_number'],
                'delivery_address' => $deliveryAddress,
                'payment_method' => $validated['payment_method'],
                'subtotal' => $cart->subtotal(),
                'delivery_fee' => $cart->deliveryFee(),
                'deposit_credit' => $depositCredit,
                'total' => $amountDue,
                'system_eta' => $systemEta,
                'status' => Order::STATUS_PENDING,
                'special_instructions' => $validated['special_instructions'] ?? null,
            ]);

            if ($applicableReservation) {
                $applicableReservation->update([
                    'deposit_status' => Reservation::DEPOSIT_STATUS_APPLIED,
                    'applied_order_id' => $order->id,
                ]);
            }

            foreach ($cartItems as $line) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'menu_item_id' => $line->item->id,
                    'item_name' => $line->item->name,
                    'unit_price' => $line->item->price,
                    'quantity' => $line->quantity,
                    'line_total' => $line->line_total,
                    'special_instructions' => $line->instructions ?: null,
                ]);
            }

            return $order;
        });

        $cart->clear();

        $successMessage = "Order {$order->order_number} placed successfully!";
        if ($order->system_eta !== null) {
            $successMessage .= " Estimated preparation time: {$order->system_eta} minutes.";
        }

        if ($invoiceUrl !== null) {
            return redirect()->away($invoiceUrl);
        }

        return redirect()
            ->route('orders.index')
            ->with('success', $successMessage);
    }

}
