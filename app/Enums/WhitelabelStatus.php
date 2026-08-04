<?php

namespace App\Enums;

enum WhitelabelStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Suspended => 'Suspended',
        };
    }

    public function isUsable(): bool
    {
        return $this === self::Active;
    }
}
