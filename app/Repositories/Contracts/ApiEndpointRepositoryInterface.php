<?php

namespace App\Repositories\Contracts;

use App\Models\ApiEndpoint;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ApiEndpointRepositoryInterface
{
    public function findPublishedBySlug(string $versionSlug, string $slug): ?ApiEndpoint;

    public function findForPreview(int $endpointId): ?ApiEndpoint;

    public function paginatePublished(?string $versionSlug = null, int $perPage = 20): LengthAwarePaginator;

    /**
     * @return Collection<int, ApiEndpoint>
     */
    public function forGroup(int $groupId, bool $publishedOnly = true): Collection;
}
