<?php

namespace App\Models;

use App\Enums\EnvironmentSlug;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ApiEnvironment extends Model
{
    use HasSlug;

    protected $fillable = [
        'name',
        'slug',
        'label',
        'base_url',
        'badge',
        'color',
        'is_default',
        'is_enabled',
        'sort_order',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'slug' => EnvironmentSlug::class,
            'is_default' => 'boolean',
            'is_enabled' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function versions(): BelongsToMany
    {
        return $this->belongsToMany(ApiVersion::class, 'api_version_environment')
            ->withPivot('base_url_override')
            ->withTimestamps();
    }

    public function examples(): HasMany
    {
        return $this->hasMany(EndpointExample::class);
    }

    public function codeSamples(): HasMany
    {
        return $this->hasMany(CodeSample::class);
    }

    public function baseUrls(): HasMany
    {
        return $this->hasMany(EndpointBaseUrl::class);
    }

    public function postmanCollections(): HasMany
    {
        return $this->hasMany(PostmanCollection::class);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(ApiCredential::class);
    }
}
