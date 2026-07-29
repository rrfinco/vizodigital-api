<?php

namespace App\Enums;

enum HttpMethod: string
{
    case Get = 'GET';
    case Post = 'POST';
    case Put = 'PUT';
    case Patch = 'PATCH';
    case Delete = 'DELETE';
    case Head = 'HEAD';
    case Options = 'OPTIONS';

    public function color(): string
    {
        return match ($this) {
            self::Get => 'emerald',
            self::Post => 'blue',
            self::Put, self::Patch => 'amber',
            self::Delete => 'rose',
            default => 'slate',
        };
    }
}
