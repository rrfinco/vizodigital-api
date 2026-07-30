<?php

namespace App\Filament\Concerns;

use App\Services\Cms\DefaultApiGroupResolver;
use Illuminate\Validation\ValidationException;

trait ResolvesEndpointCategoryGroup
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function resolveGroupFromCategory(array $data): array
    {
        $categoryId = $this->data['api_category_id'] ?? null;

        if (! filled($categoryId)) {
            throw ValidationException::withMessages([
                'data.api_category_id' => 'Please select a category.',
            ]);
        }

        $group = app(DefaultApiGroupResolver::class)->forCategory((int) $categoryId);
        $data['api_group_id'] = $group->id;

        return $data;
    }
}
