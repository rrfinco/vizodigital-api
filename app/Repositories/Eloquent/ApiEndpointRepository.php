<?php

namespace App\Repositories\Eloquent;

use App\Models\ApiEndpoint;
use App\Repositories\Contracts\ApiEndpointRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ApiEndpointRepository implements ApiEndpointRepositoryInterface
{
    /**
     * @return list<string>
     */
    private function eagerRelations(): array
    {
        return [
            'group.category',
            'version',
            'sections',
            'headers',
            'parameters',
            'requestBodies',
            'responses',
            'errors',
            'notes',
            'examples.environment',
            'codeSamples.environment',
            'relatedEndpoints.version',
        ];
    }

    public function findPublishedBySlug(string $versionSlug, string $slug): ?ApiEndpoint
    {
        return ApiEndpoint::query()
            ->published()
            ->where('slug', $slug)
            ->whereHas('version', fn ($q) => $q->where('slug', $versionSlug)->published())
            ->with($this->eagerRelations())
            ->first();
    }

    public function findForPreview(int $endpointId): ?ApiEndpoint
    {
        return ApiEndpoint::query()
            ->whereKey($endpointId)
            ->with($this->eagerRelations())
            ->first();
    }

    public function paginatePublished(?string $versionSlug = null, int $perPage = 20): LengthAwarePaginator
    {
        return ApiEndpoint::query()
            ->published()
            ->when(
                $versionSlug,
                fn ($q) => $q->whereHas('version', fn ($vq) => $vq->where('slug', $versionSlug)->published())
            )
            ->with(['group.category', 'version'])
            ->orderBy('sort_order')
            ->paginate($perPage);
    }

    public function forGroup(int $groupId, bool $publishedOnly = true): Collection
    {
        return ApiEndpoint::query()
            ->where('api_group_id', $groupId)
            ->when($publishedOnly, fn ($q) => $q->published())
            ->orderBy('sort_order')
            ->get();
    }
}
