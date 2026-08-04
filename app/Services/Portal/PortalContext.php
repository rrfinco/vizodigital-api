<?php

namespace App\Services\Portal;

use App\Models\ApiEnvironment;
use App\Models\ApiVersion;
use App\Repositories\Contracts\DocumentationRepositoryInterface;
use App\Repositories\Contracts\EnvironmentRepositoryInterface;
use App\Services\Whitelabel\WhitelabelEnvironmentUrls;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PortalContext
{
    public const SESSION_ENVIRONMENT = 'portal.environment';

    public const SESSION_VERSION = 'portal.version';

    private ?ApiVersion $version = null;

    private ?ApiEnvironment $environment = null;

    /** @var Collection<int, ApiVersion>|null */
    private ?Collection $versions = null;

    /** @var Collection<int, ApiEnvironment>|null */
    private ?Collection $environments = null;

    private bool $resolved = false;

    public function __construct(
        private readonly DocumentationRepositoryInterface $documentation,
        private readonly EnvironmentRepositoryInterface $environmentsRepo,
        private readonly WhitelabelEnvironmentUrls $whitelabelUrls,
    ) {}

    public function resolve(?Request $request = null): self
    {
        $request ??= request();

        $this->versions = $this->documentation->publishedVersions();
        $this->version = $this->resolveVersion($request);
        $this->environments = $this->whitelabelUrls->applyToCollection(
            $this->resolveEnvironmentList($this->version)
        );
        $this->environment = $this->resolveEnvironment($request);
        $this->resolved = true;

        return $this;
    }

    public function version(): ?ApiVersion
    {
        $this->ensureResolved();

        return $this->version;
    }

    public function environment(): ?ApiEnvironment
    {
        $this->ensureResolved();

        return $this->environment;
    }

    /**
     * @return Collection<int, ApiVersion>
     */
    public function versions(): Collection
    {
        $this->ensureResolved();

        return $this->versions ?? collect();
    }

    /**
     * @return Collection<int, ApiEnvironment>
     */
    public function environments(): Collection
    {
        $this->ensureResolved();

        return $this->environments ?? collect();
    }

    public function setEnvironment(string $slug): void
    {
        $environment = $this->environmentsRepo->findBySlug($slug);

        if ($environment) {
            $environment = $this->whitelabelUrls->applyToEnvironment($environment);
            session([self::SESSION_ENVIRONMENT => $environment->slug instanceof \BackedEnum
                ? $environment->slug->value
                : (string) $environment->slug]);
            $this->environment = $environment;
        }
    }

    public function setVersion(string $slug): void
    {
        $version = $this->documentation->findVersionBySlug($slug);

        if ($version) {
            session([self::SESSION_VERSION => $version->slug]);
            $this->version = $version;
        }
    }

    private function resolveVersion(Request $request): ?ApiVersion
    {
        $routeVersion = $request->route('version');

        if (is_string($routeVersion) && $routeVersion !== '') {
            $version = $this->documentation->findVersionBySlug($routeVersion);
            if ($version) {
                session([self::SESSION_VERSION => $version->slug]);

                return $version;
            }
        }

        $sessionSlug = session(self::SESSION_VERSION);
        if (is_string($sessionSlug) && $sessionSlug !== '') {
            $version = $this->versions?->firstWhere('slug', $sessionSlug)
                ?? $this->documentation->findVersionBySlug($sessionSlug);
            if ($version) {
                return $version;
            }
        }

        return $this->documentation->defaultVersion()
            ?? $this->versions?->first();
    }

    /**
     * @return Collection<int, ApiEnvironment>
     */
    private function resolveEnvironmentList(?ApiVersion $version): Collection
    {
        if ($version) {
            $attached = $version->environments()
                ->where('is_enabled', true)
                ->orderBy('api_environments.sort_order')
                ->get();

            if ($attached->isNotEmpty()) {
                return $attached;
            }
        }

        return $this->environmentsRepo->enabled();
    }

    private function resolveEnvironment(Request $request): ?ApiEnvironment
    {
        $queryEnv = $request->query('env');
        if (is_string($queryEnv) && $queryEnv !== '') {
            $environment = $this->findEnvironment($queryEnv);
            if ($environment) {
                $this->setEnvironment(
                    $environment->slug instanceof \BackedEnum
                        ? $environment->slug->value
                        : (string) $environment->slug
                );

                return $environment;
            }
        }

        $sessionSlug = session(self::SESSION_ENVIRONMENT);
        if (is_string($sessionSlug) && $sessionSlug !== '') {
            $environment = $this->findEnvironment($sessionSlug);
            if ($environment) {
                return $environment;
            }
        }

        $default = $this->environmentsRepo->default();
        if ($default && $this->environments?->contains('id', $default->id)) {
            return $this->findEnvironment(
                $default->slug instanceof \BackedEnum
                    ? $default->slug->value
                    : (string) $default->slug
            );
        }

        return $this->environments?->first()
            ?? ($default ? $this->whitelabelUrls->applyToEnvironment($default) : null);
    }

    private function findEnvironment(string $slug): ?ApiEnvironment
    {
        $fromList = $this->findInList($slug);
        if ($fromList) {
            return $fromList;
        }

        $fromRepo = $this->environmentsRepo->findBySlug($slug);

        return $fromRepo
            ? $this->whitelabelUrls->applyToEnvironment($fromRepo)
            : null;
    }

    private function findInList(string $slug): ?ApiEnvironment
    {
        return $this->environments?->first(function (ApiEnvironment $environment) use ($slug): bool {
            $value = $environment->slug instanceof \BackedEnum
                ? $environment->slug->value
                : (string) $environment->slug;

            return $value === $slug;
        });
    }

    private function ensureResolved(): void
    {
        if (! $this->resolved) {
            $this->resolve();
        }
    }
}
