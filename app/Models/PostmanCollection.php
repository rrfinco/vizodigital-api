<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Traits\HasPublishStatus;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostmanCollection extends Model
{
    use HasPublishStatus;
    use HasSlug;

    protected $fillable = [
        'api_version_id',
        'api_environment_id',
        'name',
        'slug',
        'status',
        'file_path',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
            'payload' => 'array',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ApiVersion::class, 'api_version_id');
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(ApiEnvironment::class, 'api_environment_id');
    }
}
