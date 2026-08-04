<?php

namespace App\Repositories\Eloquent;

use App\Models\ApiCategory;
use App\Models\ApiVersion;
use App\Models\ChangelogEntry;
use App\Models\DocumentationPage;
use App\Models\Faq;
use App\Models\SdkPackage;
use App\Repositories\Contracts\DocumentationRepositoryInterface;
use App\Services\Docs\DocsEndpointVisibility;
use Illuminate\Support\Collection;

class DocumentationRepository implements DocumentationRepositoryInterface
{
    public function __construct(
        private readonly DocsEndpointVisibility $endpointVisibility,
    ) {}

    /**
     * @return list<string>
     */
    private function pageRelations(): array
    {
        return ['version', 'sections'];
    }

    public function defaultVersion(): ?ApiVersion
    {
        return ApiVersion::query()
            ->where('is_default', true)
            ->first()
            ?? ApiVersion::query()->published()->orderBy('sort_order')->first();
    }

    public function findVersionBySlug(string $slug): ?ApiVersion
    {
        return ApiVersion::query()->where('slug', $slug)->first();
    }

    public function publishedVersions(): Collection
    {
        return ApiVersion::query()
            ->published()
            ->orderBy('sort_order')
            ->get();
    }

    public function publishedCategoryTree(?string $versionSlug = null): Collection
    {
        $version = $versionSlug
            ? $this->findVersionBySlug($versionSlug)
            : $this->defaultVersion();

        if (! $version) {
            return collect();
        }

        $tree = ApiCategory::query()
            ->published()
            ->where('api_version_id', $version->id)
            ->where('show_in_sidebar', true)
            ->with(['groups' => fn ($q) => $q->published()->with(['endpoints' => fn ($eq) => $eq->published()])])
            ->orderBy('sort_order')
            ->get();

        return $this->endpointVisibility->filterCategoryTree($tree, auth()->user());
    }

    public function findPublishedPageBySlug(string $versionSlug, string $slug): ?DocumentationPage
    {
        return DocumentationPage::query()
            ->published()
            ->where('slug', $slug)
            ->whereHas('version', fn ($q) => $q->where('slug', $versionSlug)->published())
            ->with($this->pageRelations())
            ->first();
    }

    public function findPageForPreview(int $pageId): ?DocumentationPage
    {
        return DocumentationPage::query()
            ->whereKey($pageId)
            ->with($this->pageRelations())
            ->first();
    }

    public function publishedFaqs(string $versionSlug): Collection
    {
        $version = $this->findVersionBySlug($versionSlug);

        if (! $version) {
            return collect();
        }

        return Faq::query()
            ->published()
            ->where(function ($q) use ($version): void {
                $q->whereNull('api_version_id')
                    ->orWhere('api_version_id', $version->id);
            })
            ->orderBy('sort_order')
            ->get();
    }

    public function publishedChangelog(string $versionSlug): Collection
    {
        $version = $this->findVersionBySlug($versionSlug);

        if (! $version) {
            return collect();
        }

        return ChangelogEntry::query()
            ->published()
            ->where('api_version_id', $version->id)
            ->orderByDesc('released_at')
            ->orderBy('sort_order')
            ->get();
    }

    public function findPublishedChangelogEntry(string $versionSlug, string $slug): ?ChangelogEntry
    {
        return ChangelogEntry::query()
            ->published()
            ->where('slug', $slug)
            ->whereHas('version', fn ($q) => $q->where('slug', $versionSlug)->published())
            ->with('version')
            ->first();
    }

    public function publishedSdkPackages(string $versionSlug): Collection
    {
        $version = $this->findVersionBySlug($versionSlug);

        if (! $version) {
            return collect();
        }

        return SdkPackage::query()
            ->published()
            ->where(function ($q) use ($version): void {
                $q->whereNull('api_version_id')
                    ->orWhere('api_version_id', $version->id);
            })
            ->orderBy('sort_order')
            ->get();
    }
}
