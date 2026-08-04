<?php

namespace App\Services\Environment;

use App\Models\ApiEndpoint;
use App\Models\ApiEnvironment;
use App\Models\EndpointBaseUrl;
use App\Services\Whitelabel\WhitelabelEnvironmentUrls;

class BaseUrlResolver
{
    public function __construct(
        private readonly WhitelabelEnvironmentUrls $whitelabelUrls,
    ) {}

    /**
     * Cascade: endpoint → group → category → version+env pivot → environment.base_url
     * (environment base_url is remapped to the active white-label UAT/API host when present)
     */
    public function forEndpoint(ApiEndpoint $endpoint, ApiEnvironment $environment): string
    {
        $endpoint->loadMissing(['group.category.version']);

        $override = $this->morphOverride($endpoint, $environment);
        if ($override !== null) {
            return $override;
        }

        if ($endpoint->group) {
            $override = $this->morphOverride($endpoint->group, $environment);
            if ($override !== null) {
                return $override;
            }

            if ($endpoint->group->category) {
                $override = $this->morphOverride($endpoint->group->category, $environment);
                if ($override !== null) {
                    return $override;
                }
            }
        }

        if ($endpoint->version) {
            $override = $this->morphOverride($endpoint->version, $environment);
            if ($override !== null) {
                return $override;
            }

            $pivotOverride = $endpoint->version
                ->environments()
                ->where('api_environments.id', $environment->id)
                ->first()
                ?->pivot
                ?->base_url_override;

            if (filled($pivotOverride)) {
                return (string) $pivotOverride;
            }
        }

        return $this->whitelabelUrls->resolve($environment);
    }

    private function morphOverride(object $urlable, ApiEnvironment $environment): ?string
    {
        $record = EndpointBaseUrl::query()
            ->where('api_environment_id', $environment->id)
            ->where('urlable_type', $urlable::class)
            ->where('urlable_id', $urlable->getKey())
            ->first();

        return $record ? rtrim($record->base_url, '/') : null;
    }
}
