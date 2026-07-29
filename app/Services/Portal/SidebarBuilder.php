<?php

namespace App\Services\Portal;

use App\DTOs\Docs\NavigationNodeDto;
use App\Models\ApiCategory;
use App\Models\ApiEndpoint;
use App\Models\ApiGroup;
use App\Models\ApiVersion;
use App\Models\NavigationItem;
use App\Repositories\Contracts\DocumentationRepositoryInterface;
use App\Repositories\Contracts\NavigationRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SidebarBuilder
{
    public function __construct(
        private readonly NavigationRepositoryInterface $navigation,
        private readonly DocumentationRepositoryInterface $documentation,
        private readonly NavigationUrlResolver $urls,
        private readonly PortalContext $portal,
    ) {}

    /**
     * @return Collection<int, NavigationNodeDto>
     */
    public function build(?ApiVersion $version = null): Collection
    {
        $version ??= $this->portal->version();
        $nodes = collect();

        $cmsItems = $this->navigation->treeForVersion($version);
        foreach ($cmsItems as $item) {
            $nodes->push($this->mapItem($item, $version));
        }

        if ($nodes->isEmpty()) {
            $nodes = $this->fallbackFromConfig();
        }

        $apiTree = $this->apiReferenceTree($version);
        if ($apiTree->isNotEmpty()) {
            $nodes->push(new NavigationNodeDto(
                label: 'API Reference',
                href: $version
                    ? route('docs.explorer', ['version' => $version->slug])
                    : null,
                active: request()->routeIs('docs.explorer', 'docs.categories.*', 'docs.groups.*', 'docs.endpoints.*'),
                children: $apiTree,
            ));
        }

        return $nodes;
    }

    private function mapItem(NavigationItem $item, ?ApiVersion $version): NavigationNodeDto
    {
        $href = $this->urls->href($item, $version);
        $children = $item->children
            ->where('is_visible', true)
            ->values()
            ->map(fn (NavigationItem $child) => $this->mapItem($child, $version));

        return new NavigationNodeDto(
            label: $item->label,
            href: $href,
            active: $this->isActive($href),
            external: $item->target_type?->value === 'url' && filled($item->url) && Str::startsWith($item->url, ['http://', 'https://']),
            children: $children,
        );
    }

    /**
     * @return Collection<int, NavigationNodeDto>
     */
    private function apiReferenceTree(?ApiVersion $version): Collection
    {
        if (! $version) {
            return collect();
        }

        $categories = $this->documentation->publishedCategoryTree($version->slug);

        return $categories->map(function (ApiCategory $category) use ($version): NavigationNodeDto {
            $categoryHref = route('docs.categories.show', [
                'version' => $version->slug,
                'category' => $category->slug,
            ]);

            $groups = $category->groups->map(function (ApiGroup $group) use ($version): NavigationNodeDto {
                $groupHref = route('docs.groups.show', [
                    'version' => $version->slug,
                    'group' => $group->slug,
                ]);

                $endpoints = $group->endpoints->map(function (ApiEndpoint $endpoint) use ($version): NavigationNodeDto {
                    $href = route('docs.endpoints.show', [
                        'version' => $version->slug,
                        'endpoint' => $endpoint->slug,
                    ]);

                    return new NavigationNodeDto(
                        label: $endpoint->name,
                        href: $href,
                        active: $this->isActive($href),
                        badge: $endpoint->method?->value,
                    );
                });

                return new NavigationNodeDto(
                    label: $group->name,
                    href: $groupHref,
                    active: $this->isActive($groupHref) || $endpoints->contains(fn (NavigationNodeDto $n) => $n->active),
                    children: $endpoints,
                );
            });

            return new NavigationNodeDto(
                label: $category->name,
                href: $categoryHref,
                active: $this->isActive($categoryHref) || $groups->contains(fn (NavigationNodeDto $n) => $n->active),
                children: $groups,
            );
        });
    }

    /**
     * @return Collection<int, NavigationNodeDto>
     */
    private function fallbackFromConfig(): Collection
    {
        return collect(config('portal.sidebar', []))->map(function (array $item): NavigationNodeDto {
            $href = filled($item['route'] ?? null) && \Illuminate\Support\Facades\Route::has($item['route'])
                ? route($item['route'])
                : null;

            return new NavigationNodeDto(
                label: $item['label'],
                href: $href,
                active: $href ? $this->isActive($href) : false,
                badge: $href ? null : 'Soon',
            );
        });
    }

    private function isActive(?string $href): bool
    {
        if (! $href) {
            return false;
        }

        $current = request()->url();
        $target = Str::before($href, '?');

        return $current === $target || Str::startsWith($current, rtrim($target, '/').'/');
    }
}
