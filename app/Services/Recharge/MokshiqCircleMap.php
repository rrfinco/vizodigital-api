<?php

namespace App\Services\Recharge;

/**
 * Normalizes operator/plan-fetch circle names to Mokshiq recharge circle strings.
 *
 * Operator fetch often returns names like "Bihar and Jharkhand"; Mokshiq expects "Bihar Jharkhand".
 */
class MokshiqCircleMap
{
    /**
     * Explicit aliases (lowercase key → Mokshiq circle name).
     *
     * @var array<string, string>
     */
    public const ALIASES = [
        'bihar and jharkhand' => 'Bihar Jharkhand',
        'bihar' => 'Bihar Jharkhand',
        'jharkhand' => 'Bihar Jharkhand',
    ];

    public static function normalize(string $circle): string
    {
        $trimmed = trim(preg_replace('/\s+/u', ' ', $circle) ?? $circle);
        if ($trimmed === '') {
            return $trimmed;
        }

        $key = strtolower($trimmed);
        if (isset(self::ALIASES[$key])) {
            return self::ALIASES[$key];
        }

        // "Foo and Bar" → "Foo Bar" (common fetch naming)
        $withoutAnd = preg_replace('/\s+and\s+/iu', ' ', $trimmed) ?? $trimmed;

        return trim(preg_replace('/\s+/u', ' ', $withoutAnd) ?? $withoutAnd);
    }
}
