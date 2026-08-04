<?php

namespace App\Enums;

enum WhitelabelDomainRole: string
{
    case Portal = 'portal';
    case Uat = 'uat';
    case Production = 'production';

    public function label(): string
    {
        return match ($this) {
            self::Portal => 'Portal (docs / panels)',
            self::Uat => 'UAT API',
            self::Production => 'Production API',
        };
    }

    public function helperText(): string
    {
        return match ($this) {
            self::Portal => 'Branded website, docs, /partner and /user. Example: portal.acme.com',
            self::Uat => 'Sandbox API host shown in docs & credentials. Example: uat.acme.com',
            self::Production => 'Live API host shown in docs & credentials. Example: api.acme.com',
        };
    }

    public static function forEnvironment(EnvironmentSlug|string $slug): ?self
    {
        $value = $slug instanceof EnvironmentSlug ? $slug->value : $slug;

        return match ($value) {
            EnvironmentSlug::Uat->value => self::Uat,
            EnvironmentSlug::Production->value => self::Production,
            default => null,
        };
    }
}
