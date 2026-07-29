<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Traits\HasPublishStatus;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ApiGroup extends Model
{
    use HasPublishStatus;
    use HasSlug;

    protected $fillable = [
        'api_category_id',
        'name',
        'slug',
        'description',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ApiCategory::class, 'api_category_id');
    }

    public function endpoints(): HasMany
    {
        return $this->hasMany(ApiEndpoint::class)->orderBy('sort_order');
    }

    public function baseUrls(): MorphMany
    {
        return $this->morphMany(EndpointBaseUrl::class, 'urlable');
    }
}
