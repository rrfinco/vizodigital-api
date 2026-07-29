<?php

namespace App\DTOs\Docs;

use App\Enums\HttpMethod;
use App\Enums\PublishStatus;
use Illuminate\Support\Collection;

readonly class EndpointDocumentDto
{
    /**
     * @param  Collection<int, SectionDto>  $sections
     * @param  Collection<int, TocItemDto>  $toc
     * @param  Collection<int, RelatedEndpointDto>  $related
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?HttpMethod $method,
        public string $path,
        public ?string $summary,
        public ?PublishStatus $status,
        public ?string $publishedAt,
        public string $versionSlug,
        public ?string $versionName,
        public ?string $categoryName,
        public ?string $groupName,
        public ?string $baseUrl,
        public ?string $environmentName,
        public bool $preview,
        public Collection $sections,
        public Collection $toc,
        public Collection $related,
    ) {}
}
