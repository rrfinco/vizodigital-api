<?php

namespace App\Models;

use App\Enums\NavigationTargetType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationItem extends Model
{
    protected $fillable = [
        'api_version_id',
        'parent_id',
        'label',
        'icon',
        'target_type',
        'target_id',
        'url',
        'route_name',
        'is_visible',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'target_type' => NavigationTargetType::class,
            'is_visible' => 'boolean',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ApiVersion::class, 'api_version_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }
}
