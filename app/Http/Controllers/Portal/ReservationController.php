<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Reservation;
use App\Models\RestaurantSetting;
use App\Models\TableGroup;
use App\Services\CartService;
use App\Services\InventoryService;
use App\Services\PaymentProofOcrService;
use App\Services\ReservationMailService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReservationController extends Controller
{
    public function index(CartService $cart): View
    {
        $reservations = auth()->check()
            ? Reservation::query()
                ->where('user_id', auth()->id())
                ->orderByDesc('reserved_at')
                ->limit(5)
                ->get()
            : collect();

        return view('portal.reservations', [
            'settings' => RestaurantSetting::current(),
            'cartCount' => $cart->count(),
            'groups' => $this->tableGroups(),
            'groupsPayload' => $this->groupsForOverview(),
            'menuPayload' => $this->menuForSelection(),
            'defaultDuration' => Reservation::DEFAULT_DURATION_MINUTES,
            'tableDeposit' => Reservation::TABLE_DEPOSIT_AMOUNT,
            'preorderDeposit' => Reservation::PREORDER_DEPOSIT_AMOUNT,
            'depositScanUrl' => route('reservations.scan-payment-proof'),
            'availabilityUrl' => route('reservations.availability'),
            'openingHoursLabel' => Reservation::OPENING_HOURS_LABEL,
            'openHour' => Reservation::OPEN_HOUR,
            'reservations' => $reservations,
        ]);
    }

    public function mine(CartService $cart): View
    {
        $reservations = Reservation::query()
            ->with('diningTable.tableGroup')
            ->where('user_id', auth()->id())
            ->orderByDesc('reserved_at')
            ->get();

        $payload = $reservations->map(fn (Reservation $reservation) => [
            'id' => $reservation->id,
            'table_label' => $reservation->diningTable?->label() ?? 'Table',
            'table_group' => $reservation->diningTable?->tableGroup?->name,
            'reserved_at_display' => $reservation->reserved_at->format('M d, Y · h:i A'),
            'ends_at_display' => $reservation->endsAt()?->format('M d, Y · h:i A'),
            'party_size' => $reservation->party_size,
            'additional_chairs' => $reservation->additional_chairs ?? 0,
            'customer_name' => $reservation->customer_name,
            'contact_number' => $reservation->contact_number,
            'email' => $reservation->email,
            'notes' => $reservation->notes,
            'status' => $reservation->status,
            'status_label' => $reservation->statusLabel(),
            'deposit_total' => (float) $reservation->deposit_total,
            'table_deposit_amount' => (float) ($reservation->table_deposit_amount ?? 500),
            'preorder_deposit_amount' => (float) ($reservation->preorder_deposit_amount ?? 0),
            'deposit_status' => $reservation->deposit_status,
            'deposit_status_label' => $reservation->depositStatusLabel(),
            'deposit_payment_method' => $reservation->deposit_payment_method,
            'can_cancel' => in_array($reservation->status, [
                Reservation::STATUS_PENDING,
                Reservation::STATUS_CONFIRMED,
            ], true),
            'cancel_url' => route('reservations.cancel', $reservation),
        ])->values()->all();

        return view('portal.my-reservations', [
            'settings' => RestaurantSetting::current(),
            'cartCount' => $cart->count(),
            'reservations' => $reservations,
            'reservationsPayload' => $payload,
        ]);
    }

    public function availability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reserved_at' => ['required', 'date'],
        ]);

        $start = Carbon::parse($validated['reserved_at']);
        $duration = Reservation::DEFAULT_DURATION_MINUTES;

        $bookedIds = Reservation::bookedTableIdsForWindow($start, $duration);
        $occupiedIds = DiningTable::query()
            ->with('activeOccupancy')
            ->get()
            ->filter(fn (DiningTable $table) => $table->isOccupied())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return response()->json([
            'within_opening_hours' => Reservation::isWithinOpeningHours($start),
            'opening_hours_label' => Reservation::OPENING_HOURS_LABEL,
            'booked_table_ids' => $bookedIds,
            'occupied_table_ids' => $occupiedIds,
            'unavailable_table_ids' => array_values(array_unique(array_merge($bookedIds, $occupiedIds))),
        ]);
    }

    public function scanPaymentProof(Request $request, PaymentProofOcrService $ocr): JsonResponse
    {
        $request->validate([
            'payment_proof' => ['required', 'image', 'max:4096'],
            'payment_method' => ['required', 'in:gcash,maya'],
        ]);

        try {
            $result = $ocr->extractFromUpload($request->file('payment_proof'));

            return response()->json([
                'reference_number' => $result['reference_number'],
                'amount_paid' => $result['amount_paid'],
                'amount_paid_label' => $result['amount_paid'] !== null
                    ? '₱'.number_format($result['amount_paid'], 2)
                    : null,
                'message' => 'Reference and amount detected from screenshot.',
            ]);
        } catch (\Throwable) {
            return response()->json([
                'message' => 'Could not read the payment screenshot. Try a clearer image.',
            ], 422);
        }
    }

    public function store(Request $request, InventoryService $inventory, ReservationMailService $mailer): RedirectResponse
    {
        $validated = $request->validate([
            'dining_table_id' => ['required', 'integer', 'exists:dining_tables,id'],
            'customer_name' => ['required', 'string', 'max:150'],
            'contact_number' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'party_size' => ['required', 'integer', 'min:1', 'max:50'],
            'additional_chairs' => ['nullable', 'integer', 'min:0', 'max:20'],
            'reserved_at' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'has_preorder' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.menu_item_id' => ['required_with:items', 'integer', 'exists:menu_items,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1', 'max:99'],
            'deposit_payment_method' => ['required', 'in:cash,gcash,maya'],
            'deposit_payment_proof' => [
                Rule::requiredIf(in_array($request->input('deposit_payment_method'), ['gcash', 'maya'], true)),
                'nullable',
                'image',
                'max:4096',
            ],
        ]);

        $table = DiningTable::query()->with('activeOccupancy')->findOrFail($validated['dining_table_id']);
        $start = Carbon::parse($validated['reserved_at']);
        $duration = Reservation::DEFAULT_DURATION_MINUTES;
        $neededExtras = max(0, $validated['party_size'] - $table->chair_count);
        $additionalChairs = max((int) ($validated['additional_chairs'] ?? 0), $neededExtras);
        $totalSeats = $table->chair_count + $additionalChairs;
        $preorderItems = collect($validated['items'] ?? [])
            ->map(fn (array $line) => [
                'menu_item_id' => (int) $line['menu_item_id'],
                'quantity' => (int) $line['quantity'],
            ])
            ->filter(fn (array $line) => $line['menu_item_id'] > 0 && $line['quantity'] > 0)
            ->values()
            ->all();
        $hasPreorder = $request->boolean('has_preorder')
            || $preorderItems !== []
            || ! empty(trim($validated['notes'] ?? ''));
        $deposits = Reservation::calculateDepositAmounts($hasPreorder);

        if (! Reservation::isWithinOpeningHours($start)) {
            return back()
                ->withInput()
                ->withErrors(['reserved_at' => Reservation::openingHoursValidationMessage()]);
        }

        if ($start->isToday() && $start->lt(now()->addHour())) {
            return back()
                ->withInput()
                ->withErrors(['reserved_at' => 'Same-day reservations must be made at least 1 hour in advance.']);
        }

        if ($table->isOccupied()) {
            return back()
                ->withInput()
                ->withErrors(['dining_table_id' => 'That table is currently occupied. Please choose another table.']);
        }

        if ($validated['party_size'] > $totalSeats) {
            return back()
                ->withInput()
                ->withErrors(['party_size' => "Your party needs {$validated['party_size']} seats. This table has {$table->chair_count} chairs — please request enough additional chairs."]);
        }

        if (Reservation::hasConflict($table->id, $start, $duration)) {
            return back()
                ->withInput()
                ->withErrors(['reserved_at' => 'That table is already reserved around that time. Please pick another time or table.']);
        }

        $depositProofPath = null;
        $depositReference = null;
        $depositAmountPaid = null;
        $depositStatus = Reservation::DEPOSIT_STATUS_PENDING_AT_RESTAURANT;
        $depositPaidAt = null;

        if ($request->hasFile('deposit_payment_proof')) {
            $file = $request->file('deposit_payment_proof');
            $depositProofPath = $this->storePaymentProof($file);

            try {
                $ocr = app(PaymentProofOcrService::class);
                $ocrResult = $ocr->extractFromUpload($file);
                $depositReference = $ocrResult['reference_number'];
                $depositAmountPaid = $ocrResult['amount_paid'];
            } catch (\Throwable) {
                //
            }

            $depositStatus = Reservation::DEPOSIT_STATUS_PAID;
            $depositPaidAt = now();
        } elseif ($validated['deposit_payment_method'] === 'cash') {
            $depositStatus = Reservation::DEPOSIT_STATUS_PENDING_AT_RESTAURANT;
        }

        $reservation = DB::transaction(function () use (
            $inventory,
            $preorderItems,
            $table,
            $validated,
            $additionalChairs,
            $deposits,
            $depositProofPath,
            $depositReference,
            $depositAmountPaid,
            $depositStatus,
            $depositPaidAt,
            $start,
            $duration
        ): Reservation {
            if ($preorderItems !== []) {
                $inventory->deduct($preorderItems);
            }

            return Reservation::query()->create([
                'user_id' => auth()->id(),
                'dining_table_id' => $table->id,
                'customer_name' => $validated['customer_name'],
                'contact_number' => $validated['contact_number'],
                'email' => $validated['email'] ?? auth()->user()?->email,
                'party_size' => $validated['party_size'],
                'additional_chairs' => $additionalChairs,
                'table_deposit_amount' => $deposits['table_deposit'],
                'preorder_deposit_amount' => $deposits['preorder_deposit'],
                'deposit_total' => $deposits['total'],
                'deposit_payment_method' => $validated['deposit_payment_method'],
                'deposit_payment_proof' => $depositProofPath,
                'deposit_payment_reference' => $depositReference,
                'deposit_payment_amount_paid' => $depositAmountPaid,
                'deposit_status' => $depositStatus,
                'deposit_paid_at' => $depositPaidAt,
                'reserved_at' => $start,
                'duration_minutes' => $duration,
                'notes' => $validated['notes'] ?? null,
                'preorder_items' => $preorderItems !== [] ? $preorderItems : null,
                'status' => Reservation::STATUS_PENDING,
            ]);
        });

        $mailer->sendCreated($reservation->load(['diningTable.tableGroup', 'user']));

        $depositMessage = '₱'.number_format($deposits['total'], 2).' deposit recorded';
        if ($depositStatus === Reservation::DEPOSIT_STATUS_PAID) {
            $depositMessage .= ' — this will be deducted from your final bill when you order.';
        } else {
            $depositMessage .= ' — please pay at the restaurant to confirm. It will be deducted from your final bill.';
        }

        return redirect()
            ->route('reservations.mine')
            ->with('success', "Reservation requested for {$table->label()} on {$start->format('M d, Y h:i A')}. {$depositMessage}");
    }

    public function cancel(Reservation $reservation, InventoryService $inventory, ReservationMailService $mailer): RedirectResponse
    {
        abort_unless($reservation->user_id === auth()->id(), 403);

        if (in_array($reservation->status, [Reservation::STATUS_CANCELLED, Reservation::STATUS_COMPLETED], true)) {
            return back()->with('info', 'This reservation can no longer be cancelled.');
        }

        $previousStatus = $reservation->status;

        DB::transaction(function () use ($reservation, $inventory): void {
            if (is_array($reservation->preorder_items) && $reservation->preorder_items !== []) {
                $inventory->restore($reservation->preorder_items);
            }

            if ($reservation->deposit_status === Reservation::DEPOSIT_STATUS_PAID) {
                $reservation->update([
                    'status' => Reservation::STATUS_CANCELLED,
                    'deposit_status' => Reservation::DEPOSIT_STATUS_REFUNDED,
                ]);
            } else {
                $reservation->update(['status' => Reservation::STATUS_CANCELLED]);
            }
        });

        $mailer->sendStatusUpdate($reservation->fresh(['diningTable.tableGroup', 'user']), $previousStatus);

        return back()->with('success', 'Reservation cancelled.');
    }

    /**
     * Find a paid reservation deposit the user can apply at checkout.
     */
    public static function findApplicableDepositForUser(int $userId, ?int $tableId = null): ?Reservation
    {
        return Reservation::query()
            ->where('user_id', $userId)
            ->where('deposit_status', Reservation::DEPOSIT_STATUS_PAID)
            ->whereIn('status', [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED])
            ->when($tableId, fn ($q) => $q->where('dining_table_id', $tableId))
            ->orderByDesc('reserved_at')
            ->first();
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
     * @return Collection<int, TableGroup>
     */
    private function tableGroups()
    {
        return TableGroup::query()
            ->with(['diningTables' => fn ($q) => $q->with('activeOccupancy')->orderBy('sort_order')->orderBy('table_number')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (TableGroup $group) => $group->diningTables->isNotEmpty())
            ->values();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function groupsForOverview(): array
    {
        return $this->tableGroups()
            ->map(fn (TableGroup $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'map_image_url' => $group->mapImageUrl(),
                'tables' => $group->diningTables->map(fn (DiningTable $table) => [
                    'id' => $table->id,
                    'table_number' => $table->table_number,
                    'chair_count' => $table->chair_count,
                    'is_occupied' => $table->isOccupied(),
                    'map_x' => $table->map_x !== null ? (float) $table->map_x : null,
                    'map_y' => $table->map_y !== null ? (float) $table->map_y : null,
                    'is_placed_on_map' => $table->isPlacedOnMap(),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function menuForSelection(): array
    {
        return Category::query()
            ->where('is_active', true)
            ->with(['availableMenuItems' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (Category $category) => $category->availableMenuItems->isNotEmpty())
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'items' => $category->availableMenuItems->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => (float) $item->price,
                    'price_label' => $item->formattedPrice(),
                    'image_url' => $item->imageUrl(),
                    'stock_level' => $item->stock_level,
                    'tracks_inventory' => $item->tracksInventory(),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }
}
