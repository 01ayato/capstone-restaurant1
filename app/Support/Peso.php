<?php

namespace App\Support;

class Peso
{
    public static function format(float|int|string|null $amount): string
    {
        return '₱'.number_format((float) $amount, 2);
    }
}
