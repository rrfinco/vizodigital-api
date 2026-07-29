<?php

namespace App\Http\Middleware;

use App\Models\ApiEnvironment;
use App\Models\ApiVersion;
use App\Services\Portal\PortalContext;
use App\Services\Portal\PortalUrlRewriter;
use App\Services\Portal\SidebarBuilder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPortalContext
{
    public function __construct(
        private readonly PortalContext $portal,
        private readonly SidebarBuilder $sidebar,
        private readonly PortalUrlRewriter $urls,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->portal->resolve($request);

        $envSlug = $this->slugValue($this->portal->environment());

        $versionUrls = $this->portal->versions()->mapWithKeys(
            fn (ApiVersion $version) => [
                $version->slug => $this->urls->forVersion($request, $version, $envSlug),
            ]
        );

        $environmentUrls = $this->portal->environments()->mapWithKeys(
            function (ApiEnvironment $environment) use ($request): array {
                $slug = $this->slugValue($environment);

                return [
                    $slug => $request->fullUrlWithQuery(['env' => $slug]),
                ];
            }
        );

        view()->share([
            'portalContext' => $this->portal,
            'portalVersion' => $this->portal->version(),
            'portalEnvironment' => $this->portal->environment(),
            'portalVersions' => $this->portal->versions(),
            'portalEnvironments' => $this->portal->environments(),
            'portalVersionUrls' => $versionUrls,
            'portalEnvironmentUrls' => $environmentUrls,
            'portalNav' => $this->sidebar->build($this->portal->version()),
        ]);

        return $next($request);
    }

    private function slugValue(ApiEnvironment|ApiVersion|null $model): ?string
    {
        if (! $model) {
            return null;
        }

        $slug = $model->slug;

        return $slug instanceof \BackedEnum ? $slug->value : (string) $slug;
    }
}
