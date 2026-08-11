<?php

namespace App\Repositories\Contracts;

use App\Models\ApiEndpoint;

interface ApiEndpointRepositoryInterface
{
    public function findPublishedBySlug(string $versionSlug, string $slug): ?ApiEndpoint;

    public function findForPreview(int $endpointId): ?ApiEndpoint;
}
