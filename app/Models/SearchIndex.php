<?php

namespace App\Models;

use App\Enums\PublishStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SearchIndex extends Model
{
    protected $table = 'search_index';

    protected $fillable = [
        'searchable_type',
        'searchable_id',
        'api_version_id',
        'type',
        'title',
        'body',
        'keywords',
        'status',
        'url',
    ];

    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
        ];
    }

    public function searchable(): MorphTo
    {
        return $this->morphTo();
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ApiVersion::class, 'api_version_id');
    }
}
