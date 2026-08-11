<?php

namespace App\Repositories\Eloquent;

use App\Models\ApiEndpoint;
use App\Repositories\Contracts\ApiEndpointRepositoryInterface;

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
}
