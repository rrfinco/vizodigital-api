<?php

namespace App\Enums;

enum PublishStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
    case Deprecated = 'deprecated';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function isPubliclyVisible(): bool
    {
        return $this === self::Published || $this === self::Deprecated;
    }
}
