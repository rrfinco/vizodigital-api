<?php

namespace App\Enums;

enum RechargeProvider: string
{
    case Roundpay = 'roundpay';
    case Mokshiq = 'mokshiq';

    public function label(): string
    {
        return match ($this) {
            self::Roundpay => 'Roundpay',
            self::Mokshiq => 'Mokshiq',
        };
    }

    public function supportsDth(): bool
    {
        return $this === self::Roundpay;
    }
}
