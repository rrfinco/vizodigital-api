<?php

namespace App\Enums;

enum EnvironmentSlug: string
{
    case Uat = 'uat';
    case Production = 'production';

    public function label(): string
    {
        return match ($this) {
            self::Uat => 'UAT (Sandbox)',
            self::Production => 'Production',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Uat => 'Sandbox',
            self::Production => 'Live',
        };
    }
}
