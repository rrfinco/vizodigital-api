<?php

namespace App\Services\Whitelabel;

use App\Enums\EnvironmentSlug;
use App\Enums\WhitelabelDomainRole;
use App\Models\ApiEnvironment;
use App\Models\User;
use App\Models\Whitelabel;
use Illuminate\Support\Collection;

class WhitelabelEnvironmentUrls
{
    public function __construct(
        private readonly WhitelabelContext $context,
    ) {}

    public function forWhitelabel(?Whitelabel $whitelabel, EnvironmentSlug|string|null $slug): ?string
    {
        if (! $whitelabel || $slug === null) {
            return null;
        }

        $role = WhitelabelDomainRole::forEnvironment($slug);
        if (! $role) {
            return null;
        }

        return $whitelabel->baseUrlForRole($role);
    }

    public function forContext(EnvironmentSlug|string|null $slug): ?string
    {
        return $this->forWhitelabel($this->context->whitelabel(), $slug);
    }

    public function forUser(?User $user, EnvironmentSlug|string|null $slug): ?string
    {
        if (! $user?->whitelabel_id) {
            return null;
        }

        $whitelabel = $user->relationLoaded('whitelabel')
            ? $user->whitelabel
            : $user->whitelabel()->with('domains')->first();

        if ($whitelabel && ! $whitelabel->relationLoaded('domains')) {
            $whitelabel->load('domains');
        }

        return $this->forWhitelabel($whitelabel, $slug);
    }

    public function resolve(
        ApiEnvironment $environment,
        ?Whitelabel $whitelabel = null,
        ?User $user = null,
    ): string {
        $slug = $environment->slug instanceof \BackedEnum
            ? $environment->slug->value
            : (string) $environment->slug;

        $override = $whitelabel
            ? $this->forWhitelabel($whitelabel, $slug)
            : ($user
                ? $this->forUser($user, $slug)
                : $this->forContext($slug));

        return rtrim((string) ($override ?: $environment->base_url), '/');
    }

    /**
     * In-memory copy with partner base_url when a domain role is configured.
     */
    public function applyToEnvironment(ApiEnvironment $environment, ?Whitelabel $whitelabel = null): ApiEnvironment
    {
        $whitelabel ??= $this->context->whitelabel();
        if (! $whitelabel) {
            return $environment;
        }

        if (! $whitelabel->relationLoaded('domains')) {
            $whitelabel->load('domains');
        }

        $slug = $environment->slug instanceof \BackedEnum
            ? $environment->slug->value
            : (string) $environment->slug;

        $override = $this->forWhitelabel($whitelabel, $slug);
        if ($override === null) {
            return $environment;
        }

        $copy = clone $environment;
        $copy->setAttribute('base_url', $override);

        return $copy;
    }

    /**
     * @param  Collection<int, ApiEnvironment>  $environments
     * @return Collection<int, ApiEnvironment>
     */
    public function applyToCollection(Collection $environments, ?Whitelabel $whitelabel = null): Collection
    {
        return $environments
            ->map(fn (ApiEnvironment $environment): ApiEnvironment => $this->applyToEnvironment($environment, $whitelabel))
            ->values();
    }
}
