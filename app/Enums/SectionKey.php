<?php

namespace App\Enums;

enum SectionKey: string
{
    case Overview = 'overview';
    case Headers = 'headers';
    case Parameters = 'parameters';
    case Body = 'body';
    case Responses = 'responses';
    case Examples = 'examples';
    case Sdk = 'sdk';
    case Errors = 'errors';
    case Webhooks = 'webhooks';
    case RateLimits = 'rate_limits';
    case Notes = 'notes';
    case TryApi = 'try_api';
    case Permissions = 'permissions';

    public function label(): string
    {
        return match ($this) {
            self::RateLimits => 'Rate Limits',
            self::TryApi => 'Try API',
            self::Sdk => 'SDK',
            default => ucfirst($this->value),
        };
    }

    public function component(): string
    {
        return 'docs.sections.'.$this->value;
    }

    /**
     * Default enabled layout for new endpoints.
     *
     * @return list<array{key: string, enabled: bool, sort: int}>
     */
    public static function defaultLayout(): array
    {
        $keys = [
            self::Overview,
            self::Headers,
            self::Parameters,
            self::Body,
            self::Responses,
            self::Examples,
            self::Sdk,
            self::Errors,
            self::Notes,
            self::RateLimits,
            self::Permissions,
            self::Webhooks,
            self::TryApi,
        ];

        $layout = [];
        foreach ($keys as $index => $key) {
            $layout[] = [
                'key' => $key->value,
                'enabled' => ! in_array($key, [self::Webhooks, self::TryApi], true),
                'sort' => $index + 1,
            ];
        }

        return $layout;
    }
}
