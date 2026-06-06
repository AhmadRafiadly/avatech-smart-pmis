<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

final class AppTime
{
    public static function timezone(): string
    {
        return (string) config('app.timezone', 'Asia/Jakarta');
    }

    public static function now(): Carbon
    {
        return Carbon::now(self::timezone());
    }

    public static function cast(?DateTimeInterface $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        return Carbon::instance($value)->timezone(self::timezone());
    }

    public static function diff(?DateTimeInterface $value, string $fallback = 'baru saja'): string
    {
        return self::cast($value)?->diffForHumans() ?? $fallback;
    }
}
