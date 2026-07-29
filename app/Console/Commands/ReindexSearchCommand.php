<?php

namespace App\Console\Commands;

use App\Services\Search\SearchIndexer;
use Illuminate\Console\Command;

class ReindexSearchCommand extends Command
{
    protected $signature = 'search:reindex';

    protected $description = 'Rebuild the portal search_index from CMS content';

    public function handle(SearchIndexer $indexer): int
    {
        $this->info('Reindexing search…');

        $count = $indexer->reindexAll();

        $this->info("Indexed {$count} records.");

        return self::SUCCESS;
    }
}
