<?php

namespace App\Traits;

use App\Enums\PublishStatus;
use Illuminate\Database\Eloquent\Builder;

trait HasPublishStatus
{
    public function initializeHasPublishStatus(): void
    {
        $this->casts['status'] = PublishStatus::class;
    }

    public function isPublished(): bool
    {
        return $this->status === PublishStatus::Published
            || $this->status === PublishStatus::Deprecated;
    }

    public function isDraft(): bool
    {
        return $this->status === PublishStatus::Draft;
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status?->isPubliclyVisible() ?? false;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereIn('status', [
            PublishStatus::Published->value,
            PublishStatus::Deprecated->value,
        ]);
    }
}
