<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MenuItem extends Model
{
    public const MAX_SIGNATURE_ITEMS = 4;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'image',
        'is_available',
        'is_signature',
        'base_prep_time',
        'station',
        'stock_level',
        'reorder_level',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_available' => 'boolean',
            'is_signature' => 'boolean',
            'stock_level' => 'integer',
            'reorder_level' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tracksInventory(): bool
    {
        return $this->stock_level !== null;
    }

    public function isLowStock(): bool
    {
        if (! $this->tracksInventory() || $this->reorder_level === null) {
            return false;
        }

        return (int) $this->stock_level <= (int) $this->reorder_level;
    }

    public function isDrinkCategory(): bool
    {
        return ($this->category?->slug ?? '') === 'drinks';
    }

    public function imageUrl(): string
    {
        if ($this->image) {
            if (str_starts_with($this->image, 'images/') && is_file(public_path($this->image))) {
                return '/'.$this->image;
            }

            if (Storage::disk('public')->exists($this->image)) {
                return Storage::disk('public')->url($this->image);
            }
        }

        return asset('images/default_image.jpg');
    }

    public function formattedPrice(): string
    {
        return '₱'.number_format((float) $this->price, 2);
    }
}
