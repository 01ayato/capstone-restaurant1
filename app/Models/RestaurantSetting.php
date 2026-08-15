<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantSetting extends Model
{
    protected $fillable = [
        'name',
        'description',
        'address',
        'phone',
        'email',
        'delivery_fee',
        'opening_hours',
    ];

    protected function casts(): array
    {
        return [
            'delivery_fee' => 'decimal:2',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'name' => "Smoke n' Grill",
            'description' => 'Enjoy premium grilled dishes and unforgettable dining experiences.',
            'delivery_fee' => 50,
        ]);
    }

    public function formattedDeliveryFee(): string
    {
        return '₱'.number_format((float) $this->delivery_fee, 2);
    }
}
