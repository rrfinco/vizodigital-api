<?php

namespace App\Repositories\Contracts;

use App\Models\ApiEnvironment;
use Illuminate\Support\Collection;

interface EnvironmentRepositoryInterface
{
    public function default(): ?ApiEnvironment;

    public function findBySlug(string $slug): ?ApiEnvironment;

    /**
     * @return Collection<int, ApiEnvironment>
     */
    public function enabled(): Collection;
}
