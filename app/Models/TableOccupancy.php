<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableOccupancy extends Model
{
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_OCCUPIED = 'occupied';

    protected $fillable = [
        'dining_table_id',
        'occupied_chairs',
        'status',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'occupied_chairs' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function diningTable(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class);
    }

    public static function occupy(DiningTable $table, int $chairs): self
    {
        $table->activeOccupancy?->update([
            'status' => self::STATUS_AVAILABLE,
            'ended_at' => now(),
        ]);

        return self::query()->create([
            'dining_table_id' => $table->id,
            'occupied_chairs' => min($chairs, $table->chair_count),
            'status' => self::STATUS_OCCUPIED,
            'started_at' => now(),
        ]);
    }

    public static function release(DiningTable $table): void
    {
        $table->activeOccupancy?->update([
            'status' => self::STATUS_AVAILABLE,
            'occupied_chairs' => 0,
            'ended_at' => now(),
        ]);
    }
}
