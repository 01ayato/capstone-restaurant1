<?php

namespace App\Services;

use App\Models\DiningTable;
use App\Models\TableGroup;
use App\Models\TableOccupancy;

class TableSessionService
{
    public const SESSION_TABLE_ID = 'sit_table_id';

    public const SESSION_SIT_CODE = 'sit_code';

    public function currentTable(): ?DiningTable
    {
        $id = session(self::SESSION_TABLE_ID);

        if (! $id) {
            return null;
        }

        return DiningTable::query()
            ->with('tableGroup')
            ->find($id);
    }

    public function hasTable(): bool
    {
        return $this->currentTable() !== null;
    }

    public function setTable(DiningTable $table, ?int $occupiedChairs = null): void
    {
        session([
            self::SESSION_TABLE_ID => $table->id,
            self::SESSION_SIT_CODE => $table->sit_code,
        ]);

        if ($occupiedChairs !== null && $occupiedChairs > 0) {
            TableOccupancy::occupy($table, min($occupiedChairs, $table->chair_count));
        }
    }

    public function clear(): void
    {
        session()->forget([self::SESSION_TABLE_ID, self::SESSION_SIT_CODE]);
    }

    public function locationLabel(): ?string
    {
        $table = $this->currentTable();

        if ($table === null) {
            return null;
        }

        return $table->tableGroup->name.' — '.$table->label();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function groupsForSelection(): array
    {
        return TableGroup::query()
            ->with(['diningTables' => fn ($q) => $q->with('activeOccupancy')->orderBy('sort_order')->orderBy('table_number')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (TableGroup $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'map_image_url' => $group->mapImageUrl(),
                'tables' => $group->diningTables->map(fn (DiningTable $table) => [
                    'id' => $table->id,
                    'table_number' => $table->table_number,
                    'chair_count' => $table->chair_count,
                    'sit_code' => $table->sit_code,
                    'occupied_chairs' => $table->occupiedChairs(),
                    'available_chairs' => $table->availableChairs(),
                    'is_occupied' => $table->isOccupied(),
                    'is_full' => $table->availableChairs() === 0 && $table->isOccupied(),
                    'map_x' => $table->map_x !== null ? (float) $table->map_x : null,
                    'map_y' => $table->map_y !== null ? (float) $table->map_y : null,
                    'is_placed_on_map' => $table->isPlacedOnMap(),
                ])->values()->all(),
            ])
            ->filter(fn (array $group) => count($group['tables']) > 0)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function currentTablePayload(): ?array
    {
        $table = $this->currentTable();

        if ($table === null) {
            return null;
        }

        return [
            'id' => $table->id,
            'table_number' => $table->table_number,
            'chair_count' => $table->chair_count,
            'sit_code' => $table->sit_code,
            'group_name' => $table->tableGroup->name,
            'label' => $table->label(),
            'location_label' => $this->locationLabel(),
        ];
    }
}
