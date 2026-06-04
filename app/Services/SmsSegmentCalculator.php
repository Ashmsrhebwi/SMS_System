<?php

namespace App\Services;

class SmsSegmentCalculator
{
    public static function segments(string $body): int
    {
        $len = mb_strlen($body);
        if ($len === 0) return 0;
        return $len <= 160 ? 1 : (int) ceil($len / 153);
    }

    public static function cost(string $body): float
    {
        $segments = self::segments($body);
        $rate = (float) config('sms.cost_per_segment', 0.0079);
        return round($segments * $rate, 4);
    }

    public static function formatCost(float $cost): string
    {
        $symbol = config('sms.currency_symbol', '$');
        return $symbol . number_format($cost, 4);
    }

    public static function formatCostShort(float $cost): string
    {
        $symbol = config('sms.currency_symbol', '$');
        return $symbol . number_format($cost, 2);
    }
}
