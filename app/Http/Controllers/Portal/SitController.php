<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\RestaurantSetting;
use App\Models\TableOccupancy;
use App\Services\CartService;
use App\Services\TableSessionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SitController extends Controller
{
    public function show(string $sitCode, CartService $cart, TableSessionService $tableSession): View
    {
        $table = DiningTable::query()
            ->with(['tableGroup', 'activeOccupancy'])
            ->where('sit_code', $sitCode)
            ->firstOrFail();

        $tableSession->setTable($table);

        return view('portal.sit', [
            'table' => $table,
            'settings' => RestaurantSetting::current(),
            'cartCount' => $cart->count(),
        ]);
    }

    public function occupy(Request $request, string $sitCode, TableSessionService $tableSession): RedirectResponse
    {
        $table = DiningTable::query()
            ->where('sit_code', $sitCode)
            ->firstOrFail();

        $validated = $request->validate([
            'occupied_chairs' => ['required', 'integer', 'min:1', 'max:'.$table->chair_count],
        ]);

        $tableSession->setTable($table, $validated['occupied_chairs']);

        return redirect()
            ->route('menu.index')
            ->with('success', "You're at {$table->label()} ({$table->tableGroup->name}). Browse the menu to order.");
    }

    public function release(string $sitCode, TableSessionService $tableSession): RedirectResponse
    {
        $table = DiningTable::query()
            ->where('sit_code', $sitCode)
            ->firstOrFail();

        TableOccupancy::release($table);
        $tableSession->clear();

        return redirect()
            ->route('menu.index')
            ->with('success', 'Table cleared. Select your seat again when ready.');
    }

    public function lookup(TableSessionService $tableSession): JsonResponse
    {
        return response()->json([
            'groups' => $tableSession->groupsForSelection(),
            'current' => $tableSession->currentTablePayload(),
        ]);
    }

    public function selectManual(Request $request, TableSessionService $tableSession): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'table_group_id' => ['required', 'exists:table_groups,id'],
            'table_number' => ['required', 'string', 'max:20'],
            'occupied_chairs' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $table = DiningTable::query()
            ->with(['tableGroup', 'activeOccupancy'])
            ->where('table_group_id', $validated['table_group_id'])
            ->where('table_number', $validated['table_number'])
            ->first();

        if ($table === null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Table not found in that area. Check the number and try again.',
                    'errors' => ['table_number' => ['Table not found in that area.']],
                ], 422);
            }

            return back()->withErrors(['table_number' => 'Table not found in that area. Check the number and try again.']);
        }

        if ($table->availableChairs() === 0 && $table->isOccupied()) {
            $message = 'This table is full. Please choose another table.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'errors' => ['table_number' => [$message]],
                ], 422);
            }

            return back()->withErrors(['table_number' => $message]);
        }

        $chairs = $validated['occupied_chairs'] ?? 1;
        $tableSession->setTable($table, $chairs);

        $message = "You're at {$table->label()} ({$table->tableGroup->name}).";

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'table' => $tableSession->currentTablePayload(),
            ]);
        }

        return redirect()
            ->back()
            ->with('success', $message);
    }

    public function clear(TableSessionService $tableSession): RedirectResponse|JsonResponse
    {
        $table = $tableSession->currentTable();
        if ($table !== null) {
            TableOccupancy::release($table);
        }

        $tableSession->clear();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Table selection cleared.']);
        }

        return redirect()
            ->back()
            ->with('success', 'Table selection cleared.');
    }
}
