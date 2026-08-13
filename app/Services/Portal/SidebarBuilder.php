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

        $gettingStarted = $this->gettingStartedNodes($version);
        if ($gettingStarted->isNotEmpty()) {
            $nodes->push(new NavigationNodeDto(
                label: 'Getting Started',
                href: null,
                active: $gettingStarted->contains(fn (NavigationNodeDto $n) => $n->active || $n->children->contains(fn ($c) => $c->active)),
                children: $gettingStarted,
            ));
        }

        $endpointSections = $this->endpointNodes($version);
        foreach ($endpointSections as $section) {
            $nodes->push($section);
        }

        $reference = $this->referenceNodes($version);
        if ($reference->isNotEmpty()) {
            $nodes->push(new NavigationNodeDto(
                label: 'Reference',
                href: null,
                active: $reference->contains(fn (NavigationNodeDto $n) => $n->active),
                children: $reference,
            ));
        }

        if ($nodes->isEmpty()) {
            return $this->fallbackFromConfig();
        }

        return $nodes;
    }

    /**
     * @return Collection<int, NavigationNodeDto>
     */
    private function gettingStartedNodes(?ApiVersion $version): Collection
    {
        $nodes = collect();
        $overviewHref = route('docs.overview');
        $referenceHrefs = $this->referenceNodes($version)
            ->map(fn (NavigationNodeDto $node) => $node->href)
            ->filter()
            ->values();

        $nodes->push(new NavigationNodeDto(
            label: 'Overview',
            href: $overviewHref,
            active: request()->routeIs('docs.overview'),
        ));

        $cmsItems = $this->navigation->treeForVersion($version);
        foreach ($cmsItems as $item) {
            $mapped = $this->mapItem($item, $version);

            // Avoid duplicate Overview — CMS foundation also seeds docs.overview.
            if ($this->sameDocsUrl($mapped->href, $overviewHref)) {
                continue;
            }

            // Reference section already owns Explorer / FAQs / Changelog / SDK.
            if ($referenceHrefs->contains(fn (?string $href) => $this->sameDocsUrl($mapped->href, $href))) {
                continue;
            }

            $nodes->push($mapped);
        }

        return $nodes->values();
    }

    /**
     * One sidebar section per published category (same headers as the API Explorer).
     *
     * @return Collection<int, NavigationNodeDto>
     */
    private function endpointNodes(?ApiVersion $version): Collection
    {
        if (! $version) {
            return collect();
        }

        $categories = $this->documentation->publishedCategoryTree($version->slug);

        return $categories
            ->map(function (ApiCategory $category) use ($version): ?NavigationNodeDto {
                $endpoints = $category->groups
                    ->flatMap(fn (ApiGroup $group) => $group->endpoints)
                    ->sortBy('sort_order')
                    ->values();

                if ($endpoints->isEmpty()) {
                    return null;
                }

                $children = $endpoints->map(function (ApiEndpoint $endpoint) use ($version): NavigationNodeDto {
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
                    label: $category->name,
                    href: null,
                    active: $children->contains(fn (NavigationNodeDto $node) => $node->active),
                    children: $children,
                );
            })
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, NavigationNodeDto>
     */
    private function referenceNodes(?ApiVersion $version): Collection
    {
        if (! $version) {
            return collect();
        }

        $nodes = collect();

        $nodes->push(new NavigationNodeDto(
            label: 'API Explorer',
            href: route('docs.explorer', ['version' => $version->slug]),
            active: request()->routeIs('docs.explorer', 'docs.categories.*', 'docs.groups.*'),
        ));

        if (\Illuminate\Support\Facades\Route::has('docs.faqs.index')) {
            $href = route('docs.faqs.index', ['version' => $version->slug]);
            $nodes->push(new NavigationNodeDto(
                label: 'FAQs',
                href: $href,
                active: $this->isActive($href),
            ));
        }

        if (\Illuminate\Support\Facades\Route::has('docs.changelog.index')) {
            $href = route('docs.changelog.index', ['version' => $version->slug]);
            $nodes->push(new NavigationNodeDto(
                label: 'Changelog',
                href: $href,
                active: request()->routeIs('docs.changelog.*'),
            ));
        }

        if (\Illuminate\Support\Facades\Route::has('docs.sdk.index')) {
            $href = route('docs.sdk.index', ['version' => $version->slug]);
            $nodes->push(new NavigationNodeDto(
                label: 'SDK',
                href: $href,
                active: $this->isActive($href),
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

        $current = rtrim(request()->url(), '/');
        $target = rtrim(Str::before($href, '?'), '/');

        if ($current === $target) {
            return true;
        }

        // Never treat the docs root (/docs) as a prefix match — it would mark Overview
        // active on every nested docs page.
        $overview = rtrim(route('docs.overview'), '/');
        if ($target === $overview) {
            return false;
        }

        return Str::startsWith($current, $target.'/');
    }

    private function sameDocsUrl(?string $left, ?string $right): bool
    {
        if (! $left || ! $right) {
            return false;
        }

        return rtrim(Str::before($left, '?'), '/') === rtrim(Str::before($right, '?'), '/');
    }
}
