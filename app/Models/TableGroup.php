<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TableGroup extends Model
{
    protected $fillable = [
        'name',
        'map_image',
        'sort_order',
    ];

    public function diningTables(): HasMany
    {
        return $this->hasMany(DiningTable::class)->orderBy('sort_order')->orderBy('table_number');
    }

    public function mapImageUrl(): ?string
    {
        if (! $this->map_image) {
            return null;
        }

        return '/'.$this->map_image;
    }
}
