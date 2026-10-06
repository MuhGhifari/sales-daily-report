<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Business date and time (Asia/Jakarta). APP_DEMO_TODAY fixes the date for demos and tests;
 * the time of day always comes from the real clock.
 */
class Clock
{
    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(config('app.timezone'));
    }

    public static function today(): string
    {
        return config('app.demo_today') ?: static::now()->toDateString();
    }

    public static function time(): string
    {
        return static::now()->format('H:i');
    }

    /** Phone numbers in any common format (0812…, 62812…, +62 812-…) normalised to 0812… */
    public static function phone(?string $value): string
    {
        $d = preg_replace('/\D/', '', (string) $value);
        if (str_starts_with($d, '62')) {
            $d = '0'.substr($d, 2);
        } elseif (str_starts_with($d, '8')) {
            $d = '0'.$d;
        }

        return $d;
    }
}
