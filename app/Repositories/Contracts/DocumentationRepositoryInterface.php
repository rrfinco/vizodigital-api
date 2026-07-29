<?php

namespace App\Repositories\Contracts;

use App\Models\ApiCategory;
use App\Models\ApiVersion;
use App\Models\ChangelogEntry;
use App\Models\DocumentationPage;
use App\Models\Faq;
use App\Models\SdkPackage;
use Illuminate\Support\Collection;

interface DocumentationRepositoryInterface
{
    public function defaultVersion(): ?ApiVersion;

    public function findVersionBySlug(string $slug): ?ApiVersion;

    /**
     * @return Collection<int, ApiVersion>
     */
    public function publishedVersions(): Collection;

    /**
     * @return Collection<int, ApiCategory>
     */
    public function publishedCategoryTree(?string $versionSlug = null): Collection;

    public function findPublishedPageBySlug(string $versionSlug, string $slug): ?DocumentationPage;

    public function findPageForPreview(int $pageId): ?DocumentationPage;

    /**
     * @return Collection<int, Faq>
     */
    public function publishedFaqs(string $versionSlug): Collection;

    /**
     * @return Collection<int, ChangelogEntry>
     */
    public function publishedChangelog(string $versionSlug): Collection;

    public function findPublishedChangelogEntry(string $versionSlug, string $slug): ?ChangelogEntry;

    /**
     * @return Collection<int, SdkPackage>
     */
    public function publishedSdkPackages(string $versionSlug): Collection;
}
