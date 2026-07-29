<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Traits\HasPublishStatus;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangelogEntry extends Model
{
    use HasPublishStatus;
    use HasSlug;

    protected $fillable = [
        'api_version_id',
        'title',
        'slug',
        'body_md',
        'status',
        'released_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
            'released_at' => 'datetime',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ApiVersion::class, 'api_version_id');
    }
}
