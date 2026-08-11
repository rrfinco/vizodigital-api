<?php

namespace App\Services\Portal;

use App\Models\ApiEndpoint;
use App\Models\ApiVersion;
use App\Models\DocumentationPage;
use Illuminate\Http\Request;

class PortalUrlRewriter
{
    public function forVersion(Request $request, ApiVersion $version, ?string $environmentSlug = null): string
    {
        $route = $request->route()?->getName();
        $query = array_filter([
            'env' => $environmentSlug ?? $request->query('env'),
        ], fn ($value) => filled($value));

        return match ($route) {
            'docs.endpoints.show' => $this->endpointInVersion($request, $version, $query),
            'docs.pages.show' => $this->pageInVersion($request, $version, $query),
            'docs.categories.show' => route('docs.explorer', ['version' => $version->slug] + $query),
            'docs.groups.show' => route('docs.explorer', ['version' => $version->slug] + $query),
            'docs.explorer' => route('docs.explorer', ['version' => $version->slug] + $query),
            default => route('docs.explorer', ['version' => $version->slug] + $query),
        };
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function endpointInVersion(Request $request, ApiVersion $version, array $query): string
    {
        $slug = $request->route('endpoint');
        $endpoint = ApiEndpoint::query()
            ->published()
            ->where('slug', $slug)
            ->where('api_version_id', $version->id)
            ->first();

        if ($endpoint) {
            return route('docs.endpoints.show', [
                'version' => $version->slug,
                'endpoint' => $endpoint->slug,
            ] + $query);
        }

        return route('docs.explorer', ['version' => $version->slug] + $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function pageInVersion(Request $request, ApiVersion $version, array $query): string
    {
        $slug = $request->route('page');
        $page = DocumentationPage::query()
            ->published()
            ->where('slug', $slug)
            ->where('api_version_id', $version->id)
            ->first();

        if ($page) {
            return route('docs.pages.show', [
                'version' => $version->slug,
                'page' => $page->slug,
            ] + $query);
        }

        return route('docs.overview', $query);
    }
}
