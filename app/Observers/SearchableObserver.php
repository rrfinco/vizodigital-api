<?php

namespace App\Observers;

use App\Services\Search\SearchIndexer;
use Illuminate\Database\Eloquent\Model;

class SearchableObserver
{
    public function __construct(
        private readonly SearchIndexer $indexer,
    ) {}

    public function saved(Model $model): void
    {
        $this->indexer->upsert($model);
    }

    public function deleted(Model $model): void
    {
        $this->indexer->remove($model);
    }
}
