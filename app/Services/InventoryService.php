<?php

namespace App\Services;

use App\Models\MenuItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /**
     * Deduct stock for tracked drink items.
     *
     * @param  array<int, array{menu_item_id: int, quantity: int}>  $lines
     *
     * @throws ValidationException
     */
    public function deduct(array $lines): void
    {
        if ($lines === []) {
            return;
        }

        DB::transaction(function () use ($lines): void {
            foreach ($this->aggregateQuantities($lines) as $menuItemId => $quantity) {
                $item = MenuItem::query()
                    ->with('category')
                    ->whereKey($menuItemId)
                    ->lockForUpdate()
                    ->first();

                if (! $item || ! $item->tracksInventory()) {
                    continue;
                }

                if ((int) $item->stock_level < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Not enough stock for {$item->name}. Available: {$item->stock_level}.",
                    ]);
                }

                $newStock = (int) $item->stock_level - $quantity;
                $item->stock_level = $newStock;

                if ($newStock === 0) {
                    $item->is_available = false;
                }

                $item->save();
            }
        });
    }

    /**
     * Restore stock previously deducted (e.g. cancelled reservation).
     *
     * @param  array<int, array{menu_item_id: int, quantity: int}>  $lines
     */
    public function restore(array $lines): void
    {
        if ($lines === []) {
            return;
        }

        DB::transaction(function () use ($lines): void {
            foreach ($this->aggregateQuantities($lines) as $menuItemId => $quantity) {
                $item = MenuItem::query()
                    ->with('category')
                    ->whereKey($menuItemId)
                    ->lockForUpdate()
                    ->first();

                if (! $item || ! $item->tracksInventory()) {
                    continue;
                }

                $item->stock_level = (int) $item->stock_level + $quantity;

                if ($item->stock_level > 0 && ! $item->is_available) {
                    $item->is_available = true;
                }

                $item->save();
            }
        });
    }

    /**
     * @param  array<int, array{menu_item_id: int, quantity: int}>  $lines
     * @return array<int, int>
     */
    private function aggregateQuantities(array $lines): array
    {
        $totals = [];

        foreach ($lines as $line) {
            $id = (int) ($line['menu_item_id'] ?? 0);
            $qty = (int) ($line['quantity'] ?? 0);

            if ($id <= 0 || $qty <= 0) {
                continue;
            }

            $totals[$id] = ($totals[$id] ?? 0) + $qty;
        }

        return $totals;
    }
}
