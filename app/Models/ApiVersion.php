<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Traits\HasPublishStatus;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ApiVersion extends Model
{
    use HasPublishStatus;
    use HasSlug;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'is_default',
        'description',
        'released_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
            'is_default' => 'boolean',
            'released_at' => 'datetime',
        ];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(ApiCategory::class)->orderBy('sort_order');
    }

    public function endpoints(): HasMany
    {
        return $this->hasMany(ApiEndpoint::class);
    }

    public function environments(): BelongsToMany
    {
        return $this->belongsToMany(ApiEnvironment::class, 'api_version_environment')
            ->withPivot('base_url_override')
            ->withTimestamps();
    }

    public function documentationPages(): HasMany
    {
        return $this->hasMany(DocumentationPage::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class)->orderBy('sort_order');
    }

    public function changelogEntries(): HasMany
    {
        return $this->hasMany(ChangelogEntry::class)->orderByDesc('released_at')->orderBy('sort_order');
    }

    public function navigationItems(): HasMany
    {
        return $this->hasMany(NavigationItem::class)->orderBy('sort_order');
    }

    public function baseUrls(): MorphMany
    {
        return $this->morphMany(EndpointBaseUrl::class, 'urlable');
    }

    public function postmanCollections(): HasMany
    {
        return $this->hasMany(PostmanCollection::class);
    }

    public function sdkPackages(): HasMany
    {
        return $this->hasMany(SdkPackage::class);
    }
}
