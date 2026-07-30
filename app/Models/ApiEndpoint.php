<?php

namespace App\Models;

use App\Enums\HttpMethod;
use App\Enums\PublishStatus;
use App\Enums\SectionKey;
use App\Traits\HasPublishStatus;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ApiEndpoint extends Model
{
    use HasPublishStatus;
    use HasSlug;

    protected $fillable = [
        'api_group_id',
        'api_version_id',
        'name',
        'slug',
        'method',
        'path',
        'summary',
        'description_md',
        'status',
        'permission_name',
        'rate_limit',
        'sort_order',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'method' => HttpMethod::class,
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (ApiEndpoint $endpoint): void {
            if ($endpoint->sections()->exists()) {
                return;
            }

            foreach (SectionKey::defaultLayout() as $section) {
                $endpoint->sections()->create([
                    'section_key' => $section['key'],
                    'enabled' => $section['enabled'],
                    'sort_order' => $section['sort'],
                ]);
            }
        });
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ApiGroup::class, 'api_group_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ApiVersion::class, 'api_version_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(EndpointSection::class)->orderBy('sort_order');
    }

    public function headers(): HasMany
    {
        return $this->hasMany(EndpointHeader::class)->orderBy('sort_order');
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(EndpointParameter::class)->orderBy('sort_order');
    }

    public function requestBodies(): HasMany
    {
        return $this->hasMany(EndpointRequestBody::class)->orderBy('sort_order');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(EndpointResponse::class)->orderBy('sort_order');
    }

    public function errors(): HasMany
    {
        return $this->hasMany(EndpointError::class)->orderBy('sort_order');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(EndpointNote::class)->orderBy('sort_order');
    }

    public function examples(): HasMany
    {
        return $this->hasMany(EndpointExample::class)->orderBy('sort_order');
    }

    public function codeSamples(): HasMany
    {
        return $this->hasMany(CodeSample::class)->orderBy('sort_order');
    }

    public function relatedEndpoints(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'endpoint_relations',
            'api_endpoint_id',
            'related_endpoint_id'
        )->withPivot(['label', 'sort_order'])->withTimestamps();
    }

    public function subscriptionPlans(): BelongsToMany
    {
        return $this->belongsToMany(SubscriptionPlan::class, 'subscription_plan_api_endpoint')
            ->withTimestamps();
    }

    public function baseUrls(): MorphMany
    {
        return $this->morphMany(EndpointBaseUrl::class, 'urlable');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
