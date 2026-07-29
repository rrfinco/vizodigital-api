<?php

namespace App\Repositories\Contracts;

use App\Models\SearchIndex;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface SearchRepositoryInterface
{
    /**
     * @return Collection<int, SearchIndex>
     */
    public function search(string $query, ?string $versionSlug = null, int $limit = 20): Collection;

    /**
     * @param  array{
     *     api_version_id: int|null,
     *     type: string,
     *     title: string,
     *     body: string|null,
     *     keywords: string|null,
     *     status: string,
     *     url: string|null
     * }  $payload
     */
    public function upsert(Model $model, array $payload): SearchIndex;

    public function deleteBySearchable(Model $model): void;
}
