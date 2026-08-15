<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\RestaurantSetting;
use Illuminate\Support\Collection;

class CartService
{
    private const SESSION_KEY = 'cart';

    /**
     * @return array<int, array{quantity: int, instructions: string}>
     */
    public function all(): array
    {
        return session(self::SESSION_KEY, []);
    }

    public function count(): int
    {
        return array_sum(array_column($this->all(), 'quantity'));
    }

    public function add(int $menuItemId, int $quantity = 1, string $instructions = ''): void
    {
        $cart = $this->all();
        $existing = $cart[$menuItemId] ?? ['quantity' => 0, 'instructions' => ''];

        $cart[$menuItemId] = [
            'quantity' => $existing['quantity'] + max(1, $quantity),
            'instructions' => $instructions ?: $existing['instructions'],
        ];

        session([self::SESSION_KEY => $cart]);
    }

    public function update(int $menuItemId, int $quantity, string $instructions = ''): void
    {
        $cart = $this->all();

        if ($quantity <= 0) {
            unset($cart[$menuItemId]);
        } else {
            $cart[$menuItemId] = [
                'quantity' => $quantity,
                'instructions' => $instructions,
            ];
        }

        session([self::SESSION_KEY => $cart]);
    }

    public function remove(int $menuItemId): void
    {
        $cart = $this->all();
        unset($cart[$menuItemId]);
        session([self::SESSION_KEY => $cart]);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * @return Collection<int, object{item: MenuItem, quantity: int, instructions: string, line_total: float}>
     */
    public function items(): Collection
    {
        $cart = $this->all();
        if ($cart === []) {
            return collect();
        }

        $menuItems = MenuItem::query()
            ->whereIn('id', array_keys($cart))
            ->where('is_available', true)
            ->get()
            ->keyBy('id');

        return collect($cart)
            ->map(function (array $entry, int $id) use ($menuItems) {
                $item = $menuItems->get($id);
                if ($item === null) {
                    return null;
                }

                return (object) [
                    'item' => $item,
                    'quantity' => $entry['quantity'],
                    'instructions' => $entry['instructions'] ?? '',
                    'line_total' => (float) $item->price * $entry['quantity'],
                ];
            })
            ->filter()
            ->values();
    }

    public function subtotal(): float
    {
        return (float) $this->items()->sum('line_total');
    }

    public function deliveryFee(): float
    {
        return 0.0;
    }

    public function total(): float
    {
        return $this->subtotal() + $this->deliveryFee();
    }

    public function isEmpty(): bool
    {
        return $this->items()->isEmpty();
    }
}
