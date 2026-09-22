<?php

namespace App\Support;

class NameMask
{
    /**
     * Masks each word of a name, showing the first half of its letters (rounded up)
     * and replacing the rest with asterisks. Words of 1-2 letters show only the
     * first letter followed by a single asterisk. Used for public-facing display only.
     */
    public static function mask(string $fullName): string
    {
        $words = preg_split('/\s+/', trim($fullName), -1, PREG_SPLIT_NO_EMPTY);

        if ($words === false || $words === []) {
            return '';
        }

        return implode(' ', array_map(self::maskWord(...), $words));
    }

    private static function maskWord(string $word): string
    {
        $length = mb_strlen($word);

        if ($length <= 2) {
            return mb_substr($word, 0, 1).'*';
        }

        $visible = (int) ceil($length / 2);

        return mb_substr($word, 0, $visible).str_repeat('*', $length - $visible);
    }
}
