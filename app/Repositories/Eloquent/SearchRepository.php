<?php

namespace App\Repositories\Eloquent;

use App\Enums\PublishStatus;
use App\Models\SearchIndex;
use App\Repositories\Contracts\SearchRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SearchRepository implements SearchRepositoryInterface
{
    public function search(string $query, ?string $versionSlug = null, int $limit = 20): Collection
    {
        $term = trim($query);

        if ($term === '') {
            return collect();
        }

        $like = '%'.$term.'%';

        return SearchIndex::query()
            ->whereIn('status', [
                PublishStatus::Published->value,
                PublishStatus::Deprecated->value,
            ])
            ->whereNotNull('url')
            ->when(
                $versionSlug,
                fn ($q) => $q->whereHas('version', fn ($vq) => $vq->where('slug', $versionSlug))
            )
            ->where(function ($q) use ($like): void {
                $q->where('title', 'like', $like)
                    ->orWhere('body', 'like', $like)
                    ->orWhere('keywords', 'like', $like);
            })
            ->orderByRaw('CASE WHEN title LIKE ? THEN 0 ELSE 1 END', [$like])
            ->orderBy('title')
            ->limit($limit)
            ->get();
    }

    public function upsert(Model $model, array $payload): SearchIndex
    {
        return SearchIndex::query()->updateOrCreate(
            [
                'searchable_type' => $model::class,
                'searchable_id' => $model->getKey(),
            ],
            $payload
        );
    }

    public function deleteBySearchable(Model $model): void
    {
        SearchIndex::query()
            ->where('searchable_type', $model::class)
            ->where('searchable_id', $model->getKey())
            ->delete();
    }
}
