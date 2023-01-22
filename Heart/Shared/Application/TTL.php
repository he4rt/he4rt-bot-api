<?php

namespace Heart\Shared\Application;

class TTL
{
    public static function fromDays(int $value): int
    {
        return $value * 60 * 60 * 24;
    }

    public static function fromHours(int $value): int
    {
        return $value * 60 * 60;
    }

    public static function fromMinutes(int $value): int
    {
        return $value * 60;
    }
}
