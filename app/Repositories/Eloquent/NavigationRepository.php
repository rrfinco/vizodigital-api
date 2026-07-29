<?php

namespace App\Repositories\Eloquent;

use App\Models\ApiVersion;
use App\Models\NavigationItem;
use App\Repositories\Contracts\NavigationRepositoryInterface;
use Illuminate\Support\Collection;

class NavigationRepository implements NavigationRepositoryInterface
{
    public function treeForVersion(?ApiVersion $version): Collection
    {
        return NavigationItem::query()
            ->whereNull('parent_id')
            ->where('is_visible', true)
            ->where(function ($query) use ($version): void {
                $query->whereNull('api_version_id');

                if ($version) {
                    $query->orWhere('api_version_id', $version->id);
                }
            })
            ->with(['children' => fn ($q) => $q->where('is_visible', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();
    }
}
