<?php

namespace App\Services\Recharge;

/**
 * Maps Roundpay / portal operator_sp_key values to Mokshiq operator name strings.
 */
class MokshiqOperatorMap
{
    /**
     * @var array<int, string>
     */
    public const SP_KEY_TO_NAME = [
        3 => 'Airtel',
        116 => 'Jio',
        37 => 'Vodafone',
        12 => 'Vodafone',
        4 => 'BSNL',
        5 => 'BSNL',

        // DTH
        51 => 'Airtel Digital TV',
        53 => 'Dish TV',
        54 => 'Sun Direct',
        // Mokshiq docs example uses "Tata Sky"
        55 => 'Tata Sky',
        56 => 'Videocon D2h',
    ];

    public static function operatorName(int $spKey): ?string
    {
        return self::SP_KEY_TO_NAME[$spKey] ?? null;
    }
}
