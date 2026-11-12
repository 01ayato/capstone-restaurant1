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
use App\Services\XenditException;
use App\Services\XenditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    public function store(
        Request $request,
        CartService $cart,
        TableSessionService $tableSession,
        InventoryService $inventory,
        XenditService $xendit
    ): RedirectResponse
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
            'payment_proof' => ['prohibited'],
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

        if ($validated['payment_method'] === 'xendit' && $amountDue <= 0) {
            return back()
                ->withInput()
                ->withErrors(['payment_method' => 'There is no remaining balance to pay online.']);
        }

        try {
            [$order, $invoiceUrl] = DB::transaction(function () use (
                $cart,
                $validated,
                $deliveryAddress,
                $applicableReservation,
                $depositCredit,
                $amountDue,
                $inventory,
                $xendit
            ): array {
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
                    'order_number' => Order::generateOrderNumber(),
                    'user_id' => Auth::id(),
                    'reservation_id' => $applicableReservation?->id,
                    'customer_name' => $validated['customer_name'],
                    'contact_number' => $validated['contact_number'],
                    'delivery_address' => $deliveryAddress,
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => $validated['payment_method'] === 'xendit'
                        ? Order::PAYMENT_STATUS_PENDING
                        : Order::PAYMENT_STATUS_PAID,
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

                $invoiceUrl = null;
                if ($validated['payment_method'] === 'xendit') {
                    $invoice = $xendit->createInvoice(
                        "order-{$order->id}",
                        (float) $order->total,
                        "Smoke 'n' Grill order {$order->order_number}",
                        route('orders.index'),
                        route('orders.index'),
                    );

                    $order->update([
                        'xendit_invoice_id' => $invoice['id'],
                        'xendit_invoice_url' => $invoice['url'],
                    ]);
                    $invoiceUrl = $invoice['url'];
                }

                return [$order, $invoiceUrl];
            });
        } catch (XenditException $exception) {
            return back()
                ->withInput()
                ->withErrors(['payment_method' => $exception->getMessage()]);
        }

        $cart->clear();

        if ($invoiceUrl !== null) {
            return redirect()->away($invoiceUrl);
        }

        $successMessage = "Order {$order->order_number} placed successfully!";
        if ($order->system_eta !== null) {
            $successMessage .= " Estimated preparation time: {$order->system_eta} minutes.";
        }

        return redirect()
            ->route('orders.index')
            ->with('success', $successMessage);
    }
}
