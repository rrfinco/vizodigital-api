<?php

namespace App\Services\Portal;

use App\Enums\NavigationTargetType;
use App\Models\ApiCategory;
use App\Models\ApiEndpoint;
use App\Models\ApiGroup;
use App\Models\ApiVersion;
use App\Models\DocumentationPage;
use App\Models\NavigationItem;
use Illuminate\Support\Facades\Route;

class NavigationUrlResolver
{
    public function href(NavigationItem $item, ?ApiVersion $version = null): ?string
    {
        if (filled($item->route_name) && Route::has($item->route_name)) {
            return $this->namedRoute($item->route_name, $version);
        }

        if ($item->target_type === NavigationTargetType::Url && filled($item->url)) {
            return $item->url;
        }

        $versionSlug = $version?->slug;

        return match ($item->target_type) {
            NavigationTargetType::Page => $this->pageUrl($item->target_id, $versionSlug),
            NavigationTargetType::Endpoint => $this->endpointUrl($item->target_id, $versionSlug),
            NavigationTargetType::Category => $this->categoryUrl($item->target_id, $versionSlug),
            NavigationTargetType::Group => $this->groupUrl($item->target_id, $versionSlug),
            NavigationTargetType::Explorer => $versionSlug
                ? route('docs.explorer', ['version' => $versionSlug])
                : route('docs.overview'),
            NavigationTargetType::Url => $item->url,
            default => null,
        };
    }

    private function namedRoute(string $name, ?ApiVersion $version): ?string
    {
        $needsVersion = in_array($name, [
            'docs.explorer',
            'docs.faqs.index',
            'docs.changelog.index',
            'docs.sdk.index',
        ], true);

        try {
            if ($needsVersion) {
                if (! $version?->slug) {
                    return null;
                }

                return route($name, ['version' => $version->slug]);
            }

            return route($name);
        } catch (\Throwable) {
            return null;
        }
    }

    private function pageUrl(?int $id, ?string $versionSlug): ?string
    {
        if (! $id) {
            return null;
        }

        $page = DocumentationPage::query()->with('version')->find($id);
        if (! $page) {
            return null;
        }

        $slug = $versionSlug ?? $page->version?->slug;
        if (! $slug) {
            return null;
        }

        return route('docs.pages.show', ['version' => $slug, 'page' => $page->slug]);
    }

    private function endpointUrl(?int $id, ?string $versionSlug): ?string
    {
        if (! $id) {
            return null;
        }

        $endpoint = ApiEndpoint::query()->with('version')->find($id);
        if (! $endpoint) {
            return null;
        }

        $slug = $versionSlug ?? $endpoint->version?->slug;
        if (! $slug) {
            return null;
        }

        return route('docs.endpoints.show', ['version' => $slug, 'endpoint' => $endpoint->slug]);
    }

    private function categoryUrl(?int $id, ?string $versionSlug): ?string
    {
        if (! $id) {
            return null;
        }

        $category = ApiCategory::query()->with('version')->find($id);
        if (! $category) {
            return null;
        }

        $slug = $versionSlug ?? $category->version?->slug;
        if (! $slug) {
            return null;
        }

        return route('docs.categories.show', ['version' => $slug, 'category' => $category->slug]);
    }

    private function groupUrl(?int $id, ?string $versionSlug): ?string
    {
        if (! $id) {
            return null;
        }

        $group = ApiGroup::query()->with('category.version')->find($id);
        if (! $group) {
            return null;
        }

        $slug = $versionSlug ?? $group->category?->version?->slug;
        if (! $slug) {
            return null;
        }

        return route('docs.groups.show', ['version' => $slug, 'group' => $group->slug]);
    }
}
