<?php

namespace App\Services\Cms;

use App\Enums\PublishStatus;
use App\Models\ApiCategory;
use App\Models\ApiGroup;

class DefaultApiGroupResolver
{
    public const NAME = 'APIs';

    public const SLUG = 'apis';

    public function forCategory(ApiCategory|int $category): ApiGroup
    {
        $categoryId = $category instanceof ApiCategory ? $category->id : $category;

        return ApiGroup::query()->firstOrCreate(
            [
                'api_category_id' => $categoryId,
                'slug' => self::SLUG,
            ],
            [
                'name' => self::NAME,
                'status' => PublishStatus::Published,
                'sort_order' => 0,
            ],
        );
    }

    public function isDefault(?ApiGroup $group): bool
    {
        return $group !== null && $group->slug === self::SLUG;
    }
}
