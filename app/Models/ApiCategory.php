<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Traits\HasPublishStatus;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ApiCategory extends Model
{
    use HasPublishStatus;
    use HasSlug;

    protected $fillable = [
        'api_version_id',
        'name',
        'slug',
        'description',
        'icon',
        'status',
        'show_in_sidebar',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
            'show_in_sidebar' => 'boolean',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ApiVersion::class, 'api_version_id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(ApiGroup::class)->orderBy('sort_order');
    }

    public function baseUrls(): MorphMany
    {
        return $this->morphMany(EndpointBaseUrl::class, 'urlable');
    }
}
