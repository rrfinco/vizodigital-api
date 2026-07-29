<?php

namespace App\Repositories\Eloquent;

use App\Models\ApiEnvironment;
use App\Repositories\Contracts\EnvironmentRepositoryInterface;
use Illuminate\Support\Collection;

class EnvironmentRepository implements EnvironmentRepositoryInterface
{
    public function default(): ?ApiEnvironment
    {
        return ApiEnvironment::query()
            ->where('is_enabled', true)
            ->where('is_default', true)
            ->first()
            ?? ApiEnvironment::query()->where('is_enabled', true)->orderBy('sort_order')->first();
    }

    public function findBySlug(string $slug): ?ApiEnvironment
    {
        return ApiEnvironment::query()
            ->where('slug', $slug)
            ->where('is_enabled', true)
            ->first();
    }

    public function enabled(): Collection
    {
        return ApiEnvironment::query()
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->get();
    }
}
