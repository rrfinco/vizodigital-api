<?php

namespace App\Repositories\Contracts;

use App\Models\ApiVersion;
use App\Models\NavigationItem;
use Illuminate\Support\Collection;

interface NavigationRepositoryInterface
{
    /**
     * Visible root navigation items for a version (includes global null-version items).
     *
     * @return Collection<int, NavigationItem>
     */
    public function treeForVersion(?ApiVersion $version): Collection;
}
