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
use App\Services\PaymentProofOcrService;
use App\Services\TableSessionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

    public function scanPaymentProof(Request $request, PaymentProofOcrService $ocr): JsonResponse
    {
        $request->validate([
            'payment_proof' => ['required', 'image', 'max:4096'],
            'payment_method' => ['required', 'in:gcash'],
        ]);

        try {
            $result = $ocr->extractFromUpload($request->file('payment_proof'));

            return response()->json([
                'reference_number' => $result['reference_number'],
                'amount_paid' => $result['amount_paid'],
                'amount_paid_label' => $result['amount_paid'] !== null
                    ? '₱'.number_format($result['amount_paid'], 2)
                    : null,
                'message' => $this->ocrSummaryMessage($result),
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => 'Could not read the payment screenshot. Try a clearer image.',
                'error' => $exception->getMessage(),
            ], 422);
        }
    }

    public function store(Request $request, CartService $cart, TableSessionService $tableSession, PaymentProofOcrService $ocr, InventoryService $inventory): RedirectResponse
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
            'payment_method' => ['required', 'in:cash,gcash'],
            'payment_proof' => [
                Rule::requiredIf(in_array($request->input('payment_method'), ['gcash'], true)),
                'nullable',
                'image',
                'max:4096',
            ],
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

        $paymentProofPath = null;
        $paymentReference = null;
        $paymentAmountPaid = null;

        if ($request->hasFile('payment_proof')) {
            $file = $request->file('payment_proof');
            $paymentProofPath = $this->storePaymentProof($file);

            try {
                $ocrResult = $ocr->extractFromUpload($file);
                $paymentReference = $ocrResult['reference_number'];
                $paymentAmountPaid = $ocrResult['amount_paid'];
            } catch (\Throwable) {
                // Order can still proceed; staff can verify manually from the image.
            }
        }

        $order = DB::transaction(function () use ($cart, $validated, $deliveryAddress, $paymentProofPath, $paymentReference, $paymentAmountPaid, $applicableReservation, $depositCredit, $amountDue, $inventory) {
            $inventoryLines = [];
            foreach ($cart->items() as $line) {
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
                'payment_proof' => $paymentProofPath,
                'payment_reference' => $paymentReference,
                'payment_amount_paid' => $paymentAmountPaid,
                'subtotal' => $cart->subtotal(),
                'delivery_fee' => $cart->deliveryFee(),
                'deposit_credit' => $depositCredit,
                'total' => $amountDue,
                'status' => Order::STATUS_PENDING,
                'special_instructions' => $validated['special_instructions'] ?? null,
            ]);

            if ($applicableReservation) {
                $applicableReservation->update([
                    'deposit_status' => Reservation::DEPOSIT_STATUS_APPLIED,
                    'applied_order_id' => $order->id,
                ]);
            }

            foreach ($cart->items() as $line) {
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

        if ($order->payment_reference || $order->payment_amount_paid) {
            $parts = [];
            if ($order->payment_reference) {
                $parts[] = "Ref: {$order->payment_reference}";
            }
            if ($order->payment_amount_paid) {
                $parts[] = 'Paid: ₱'.number_format((float) $order->payment_amount_paid, 2);
            }
            $successMessage .= ' ('.implode(' · ', $parts).')';
        }

        return redirect()
            ->route('orders.index')
            ->with('success', $successMessage);
    }

    private function storePaymentProof(UploadedFile $file): string
    {
        $directory = public_path('images/payment-proofs');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'images/payment-proofs/'.$filename;
    }

    /**
     * @param  array{reference_number: ?string, amount_paid: ?float, raw_text: string}  $result
     */
    private function ocrSummaryMessage(array $result): string
    {
        if ($result['reference_number'] && $result['amount_paid'] !== null) {
            return 'Reference and amount detected from screenshot.';
        }

        if ($result['reference_number']) {
            return 'Reference number detected. Amount was not found — double-check the screenshot.';
        }

        if ($result['amount_paid'] !== null) {
            return 'Amount detected. Reference number was not found — double-check the screenshot.';
        }

        return 'Could not detect reference or amount. Try a clearer screenshot.';
    }
}
