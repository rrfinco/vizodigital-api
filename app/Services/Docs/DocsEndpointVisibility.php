<?php

namespace App\Services\Docs;

use App\Models\ApiCategory;
use App\Models\ApiEndpoint;
use App\Models\ApiGroup;
use App\Models\User;
use App\Models\UserPlanApiAccess;
use Illuminate\Support\Collection;

class DocsEndpointVisibility
{
    /**
     * @return Collection<int, string>
     */
    public function activeServiceKeys(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        return UserPlanApiAccess::query()
            ->where('user_id', $user->id)
            ->where('status', true)
            ->pluck('service')
            ->map(fn ($service) => (string) $service)
            ->values();
    }

    public function canView(?User $user, ?string $accessServiceKey): bool
    {
        if (! filled($accessServiceKey)) {
            return true;
        }

        return $this->activeServiceKeys($user)->contains((string) $accessServiceKey);
    }

    public function canViewEndpoint(?User $user, ApiEndpoint $endpoint): bool
    {
        return $this->canView($user, $endpoint->access_service_key);
    }

    /**
     * Filter published category → group → endpoint trees for docs/sidebar/explorer.
     *
     * @param  Collection<int, ApiCategory>  $categories
     * @return Collection<int, ApiCategory>
     */
    public function filterCategoryTree(Collection $categories, ?User $user): Collection
    {
        $keys = $this->activeServiceKeys($user);

        return $categories
            ->map(function (ApiCategory $category) use ($keys): ApiCategory {
                $groups = $category->groups
                    ->map(function (ApiGroup $group) use ($keys): ApiGroup {
                        $endpoints = $group->endpoints
                            ->filter(fn (ApiEndpoint $endpoint): bool => $this->endpointAllowed($endpoint, $keys))
                            ->values();

                        $group->setRelation('endpoints', $endpoints);

                        return $group;
                    })
                    ->values();

                $category->setRelation('groups', $groups);

                return $category;
            })
            ->values();
    }

    /**
     * @param  Collection<int, ApiEndpoint>  $endpoints
     * @return Collection<int, ApiEndpoint>
     */
    public function filterEndpoints(Collection $endpoints, ?User $user): Collection
    {
        $keys = $this->activeServiceKeys($user);

        return $endpoints
            ->filter(fn (ApiEndpoint $endpoint): bool => $this->endpointAllowed($endpoint, $keys))
            ->values();
    }

    /**
     * @param  Collection<int, string>  $keys
     */
    private function endpointAllowed(ApiEndpoint $endpoint, Collection $keys): bool
    {
        $service = $endpoint->access_service_key;

        if (! filled($service)) {
            return true;
        }

        return $keys->contains((string) $service);
    }
}
