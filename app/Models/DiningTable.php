<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DiningTable extends Model
{
    protected $fillable = [
        'table_group_id',
        'table_number',
        'chair_count',
        'sit_code',
        'sort_order',
        'map_x',
        'map_y',
    ];

    protected function casts(): array
    {
        return [
            'chair_count' => 'integer',
            'sort_order' => 'integer',
            'map_x' => 'float',
            'map_y' => 'float',
        ];
    }

    public function tableGroup(): BelongsTo
    {
        return $this->belongsTo(TableGroup::class);
    }

    public function activeOccupancy(): HasOne
    {
        return $this->hasOne(TableOccupancy::class)
            ->where('status', TableOccupancy::STATUS_OCCUPIED)
            ->whereNull('ended_at')
            ->latestOfMany();
    }

    public function sitUrl(): string
    {
        return url('/sit/'.$this->sit_code);
    }

    public function label(): string
    {
        return 'Table '.$this->table_number;
    }

    public function occupiedChairs(): int
    {
        return (int) ($this->activeOccupancy?->occupied_chairs ?? 0);
    }

    public function isOccupied(): bool
    {
        return $this->occupiedChairs() > 0;
    }

    public function availableChairs(): int
    {
        return max(0, $this->chair_count - $this->occupiedChairs());
    }

    public function isPlacedOnMap(): bool
    {
        return $this->map_x !== null && $this->map_y !== null;
    }
}
