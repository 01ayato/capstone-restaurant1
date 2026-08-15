<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\MenuItem;
use App\Models\Reservation;
use App\Models\RestaurantSetting;
use App\Models\TableGroup;
use App\Services\InventoryService;
use App\Services\ReservationMailService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReservationController extends Controller
{
    public function index(): View
    {
        $reservations = Reservation::query()
            ->with(['user', 'diningTable.tableGroup'])
            ->latest('reserved_at')
            ->paginate(20);

        $groups = TableGroup::query()
            ->with(['diningTables' => fn ($q) => $q->orderBy('sort_order')->orderBy('table_number')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (TableGroup $group) => $group->diningTables->isNotEmpty())
            ->values();

        $drinkItems = MenuItem::query()
            ->with('category')
            ->whereHas('category', fn ($q) => $q->where('slug', 'drinks'))
            ->where('is_available', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.reservations.index', [
            'reservations' => $reservations,
            'statuses' => Reservation::STATUS_LABELS,
            'groups' => $groups,
            'drinkItems' => $drinkItems,
            'pageInitial' => [
                'reservations' => $reservations->getCollection()->map(fn (Reservation $reservation) => [
                    'id' => $reservation->id,
                    'customer_name' => $reservation->customer_name,
                    'contact_number' => $reservation->contact_number,
                    'email' => $reservation->email,
                    'party_size' => $reservation->party_size,
                    'additional_chairs' => $reservation->additional_chairs ?? 0,
                    'reserved_at' => $reservation->reserved_at->format('Y-m-d\TH:i'),
                    'reserved_at_display' => $reservation->reserved_at->format('M d, Y h:i A'),
                    'ends_at_display' => $reservation->endsAt()?->format('M d, Y h:i A'),
                    'dining_table_id' => $reservation->dining_table_id,
                    'table_label' => $reservation->diningTable?->label(),
                    'table_group' => $reservation->diningTable?->tableGroup?->name,
                    'notes' => $reservation->notes,
                    'preorder_items' => $reservation->preorder_items ?? [],
                    'status' => $reservation->status,
                    'status_label' => $reservation->statusLabel(),
                    'deposit_total' => (float) $reservation->deposit_total,
                    'table_deposit_amount' => (float) ($reservation->table_deposit_amount ?? 500),
                    'preorder_deposit_amount' => (float) ($reservation->preorder_deposit_amount ?? 0),
                    'deposit_status' => $reservation->deposit_status,
                    'deposit_status_label' => $reservation->depositStatusLabel(),
                    'deposit_payment_method' => $reservation->deposit_payment_method,
                    'user_name' => $reservation->user?->name,
                    'update_url' => route('admin.reservations.update', $reservation),
                    'status_url' => route('admin.reservations.status', $reservation),
                    'notify_url' => route('admin.reservations.notify', $reservation),
                    'receipt_url' => route('admin.reservations.receipt', $reservation),
                    'can_email' => $reservation->notificationEmail() !== null,
                ])->values()->all(),
                'statuses' => Reservation::STATUS_LABELS,
                'drinkItems' => $drinkItems->map(fn (MenuItem $item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'stock_level' => $item->stock_level,
                    'tracks_inventory' => $item->tracksInventory(),
                ])->values()->all(),
                'groups' => $groups->map(fn (TableGroup $group) => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'tables' => $group->diningTables->map(fn (DiningTable $table) => [
                        'id' => $table->id,
                        'table_number' => $table->table_number,
                        'chair_count' => $table->chair_count,
                    ])->values()->all(),
                ])->values()->all(),
                'openCreate' => session()->has('errors'),
            ],
        ]);
    }

    public function store(Request $request, InventoryService $inventory, ReservationMailService $mailer): RedirectResponse
    {
        $request->merge([
            'dining_table_id' => $request->input('dining_table_id') ?: null,
            'additional_chairs' => $request->input('additional_chairs', 0),
            'items' => collect($request->input('items', []))
                ->filter(fn ($line) => (int) ($line['quantity'] ?? 0) > 0)
                ->values()
                ->all(),
        ]);

        $validated = $request->validate([
            'dining_table_id' => ['nullable', 'integer', 'exists:dining_tables,id'],
            'customer_name' => ['required', 'string', 'max:150'],
            'contact_number' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'party_size' => ['required', 'integer', 'min:1', 'max:50'],
            'additional_chairs' => ['nullable', 'integer', 'min:0', 'max:20'],
            'reserved_at' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['nullable', 'array'],
            'items.*.menu_item_id' => ['required_with:items', 'integer', 'exists:menu_items,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1', 'max:99'],
        ]);

        $duration = Reservation::DEFAULT_DURATION_MINUTES;
        $additionalChairs = (int) ($validated['additional_chairs'] ?? 0);
        $preorderItems = collect($validated['items'] ?? [])
            ->map(fn (array $line) => [
                'menu_item_id' => (int) $line['menu_item_id'],
                'quantity' => (int) $line['quantity'],
            ])
            ->filter(fn (array $line) => $line['menu_item_id'] > 0 && $line['quantity'] > 0)
            ->values()
            ->all();

        if (! empty($validated['dining_table_id'])) {
            $table = DiningTable::query()->with('activeOccupancy')->find($validated['dining_table_id']);
            $start = Carbon::parse($validated['reserved_at']);
            $neededExtras = max(0, $validated['party_size'] - ($table?->chair_count ?? 0));
            $additionalChairs = max($additionalChairs, $neededExtras);
            $totalSeats = ($table?->chair_count ?? 0) + $additionalChairs;

            if (! Reservation::isWithinOpeningHours($start)) {
                return back()->withInput()->withErrors([
                    'reserved_at' => Reservation::openingHoursValidationMessage(),
                ]);
            }

            if ($table?->isOccupied()) {
                return back()->withInput()->withErrors([
                    'dining_table_id' => 'That table is currently occupied.',
                ]);
            }

            if ($table && $validated['party_size'] > $totalSeats) {
                return back()->withInput()->withErrors([
                    'party_size' => "That table only seats {$table->chair_count}".($additionalChairs > 0 ? " (+ {$additionalChairs} extra chairs)" : '').'.',
                ]);
            }

            if ($table && Reservation::hasConflict($table->id, $start, $duration)) {
                return back()->withInput()->withErrors([
                    'reserved_at' => 'That table is already reserved around that time.',
                ]);
            }
        }

        $reservation = DB::transaction(function () use ($validated, $additionalChairs, $duration, $preorderItems, $inventory): Reservation {
            if ($preorderItems !== []) {
                $inventory->deduct($preorderItems);
            }

            unset($validated['items']);

            return Reservation::query()->create($validated + [
                'additional_chairs' => $additionalChairs,
                'duration_minutes' => $duration,
                'preorder_items' => $preorderItems !== [] ? $preorderItems : null,
                'status' => Reservation::STATUS_PENDING,
            ]);
        });

        $mailer->sendCreated($reservation);

        return back()->with('success', 'Reservation created.');
    }

    public function update(Request $request, Reservation $reservation, InventoryService $inventory, ReservationMailService $mailer): RedirectResponse
    {
        $request->merge([
            'dining_table_id' => $request->input('dining_table_id') ?: null,
            'additional_chairs' => $request->input('additional_chairs', 0),
        ]);

        $validated = $request->validate([
            'dining_table_id' => ['nullable', 'integer', 'exists:dining_tables,id'],
            'customer_name' => ['required', 'string', 'max:150'],
            'contact_number' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'party_size' => ['required', 'integer', 'min:1', 'max:50'],
            'additional_chairs' => ['nullable', 'integer', 'min:0', 'max:20'],
            'reserved_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(Reservation::STATUSES)],
        ]);

        $duration = $reservation->duration_minutes ?? Reservation::DEFAULT_DURATION_MINUTES;
        $additionalChairs = (int) ($validated['additional_chairs'] ?? 0);
        $start = Carbon::parse($validated['reserved_at']);

        if (! empty($validated['dining_table_id'])) {
            $table = DiningTable::query()->with('activeOccupancy')->find($validated['dining_table_id']);
            $neededExtras = max(0, $validated['party_size'] - ($table?->chair_count ?? 0));
            $additionalChairs = max($additionalChairs, $neededExtras);
            $totalSeats = ($table?->chair_count ?? 0) + $additionalChairs;

            if (! Reservation::isWithinOpeningHours($start)) {
                return back()->withInput()->withErrors([
                    'reserved_at' => Reservation::openingHoursValidationMessage(),
                ]);
            }

            if ($table?->isOccupied()) {
                return back()->withInput()->withErrors([
                    'dining_table_id' => 'That table is currently occupied.',
                ]);
            }

            if ($table && $validated['party_size'] > $totalSeats) {
                return back()->withInput()->withErrors([
                    'party_size' => "That table only seats {$table->chair_count}".($additionalChairs > 0 ? " (+ {$additionalChairs} extra chairs)" : '').'.',
                ]);
            }

            if (
                in_array($validated['status'], Reservation::BLOCKING_STATUSES, true)
                && $table
                && Reservation::hasConflict($table->id, $start, $duration, $reservation->id)
            ) {
                return back()->withInput()->withErrors([
                    'reserved_at' => 'That table is already reserved around that time.',
                ]);
            }
        }

        $previousStatus = $reservation->status;
        $becameCompleted = $validated['status'] === Reservation::STATUS_COMPLETED
            && $previousStatus !== Reservation::STATUS_COMPLETED;

        DB::transaction(function () use ($reservation, $validated, $additionalChairs, $start, $inventory): void {
            if (
                $validated['status'] === Reservation::STATUS_CANCELLED
                && $reservation->status !== Reservation::STATUS_CANCELLED
                && is_array($reservation->preorder_items)
                && $reservation->preorder_items !== []
            ) {
                $inventory->restore($reservation->preorder_items);
            }

            $reservation->update([
                'dining_table_id' => $validated['dining_table_id'] ?? null,
                'customer_name' => $validated['customer_name'],
                'contact_number' => $validated['contact_number'],
                'email' => $validated['email'] ?? null,
                'party_size' => $validated['party_size'],
                'additional_chairs' => $additionalChairs,
                'reserved_at' => $start,
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'],
            ]);
        });

        if ($previousStatus !== $validated['status']) {
            $mailer->sendStatusUpdate($reservation->fresh(['diningTable.tableGroup', 'user']), $previousStatus);
        }

        $redirect = back()->with('success', 'Reservation updated.');

        if ($becameCompleted) {
            $redirect->with('print_slip_url', route('admin.reservations.receipt', $reservation));
        }

        return $redirect;
    }

    public function updateStatus(Request $request, Reservation $reservation, InventoryService $inventory, ReservationMailService $mailer): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Reservation::STATUSES)],
        ]);

        if (
            $validated['status'] === Reservation::STATUS_CONFIRMED
            && $reservation->dining_table_id
            && Reservation::hasConflict(
                $reservation->dining_table_id,
                $reservation->reserved_at,
                $reservation->duration_minutes ?? Reservation::DEFAULT_DURATION_MINUTES,
                $reservation->id
            )
        ) {
            return back()->withErrors([
                'status' => 'Cannot confirm: another reservation already holds that table at this time.',
            ]);
        }

        $previousStatus = $reservation->status;
        $becameCompleted = $validated['status'] === Reservation::STATUS_COMPLETED
            && $previousStatus !== Reservation::STATUS_COMPLETED;

        DB::transaction(function () use ($reservation, $validated, $inventory): void {
            if (
                $validated['status'] === Reservation::STATUS_CANCELLED
                && $reservation->status !== Reservation::STATUS_CANCELLED
                && is_array($reservation->preorder_items)
                && $reservation->preorder_items !== []
            ) {
                $inventory->restore($reservation->preorder_items);
            }

            $reservation->update(['status' => $validated['status']]);
        });

        if ($previousStatus !== $validated['status']) {
            $mailer->sendStatusUpdate($reservation->fresh(['diningTable.tableGroup', 'user']), $previousStatus);
        }

        $redirect = back()->with('success', 'Reservation status updated.');

        if ($becameCompleted) {
            $redirect->with('print_slip_url', route('admin.reservations.receipt', $reservation));
        }

        return $redirect;
    }

    public function notify(Reservation $reservation, ReservationMailService $mailer): RedirectResponse
    {
        if ($reservation->notificationEmail() === null) {
            return back()->with('error', 'This reservation has no email address.');
        }

        if (! $mailer->resend($reservation)) {
            return back()->with('error', 'Could not send the email. Check your Brevo / mail settings.');
        }

        return back()->with('success', 'Reservation email sent to '.$reservation->notificationEmail().'.');
    }

    public function destroy(Reservation $reservation, InventoryService $inventory): RedirectResponse
    {
        DB::transaction(function () use ($reservation, $inventory): void {
            if (
                $reservation->status !== Reservation::STATUS_CANCELLED
                && is_array($reservation->preorder_items)
                && $reservation->preorder_items !== []
            ) {
                $inventory->restore($reservation->preorder_items);
            }

            $reservation->delete();
        });

        return back()->with('success', 'Reservation deleted.');
    }

    public function receipt(Reservation $reservation): View
    {
        $reservation->load(['diningTable.tableGroup', 'user']);

        return view('admin.reservations.receipt', [
            'reservation' => $reservation,
            'settings' => RestaurantSetting::current(),
        ]);
    }
}
