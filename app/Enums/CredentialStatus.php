<?php

namespace App\Enums;

enum CredentialStatus: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Locked = 'locked';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Pending => 'Pending approval',
            self::Locked => 'Locked',
            self::Revoked => 'Revoked',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Pending => 'warning',
            self::Locked => 'gray',
            self::Revoked => 'danger',
        };
    }

    public function isUsable(): bool
    {
        return $this === self::Active;
    }
}
