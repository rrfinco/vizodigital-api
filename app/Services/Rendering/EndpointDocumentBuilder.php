<?php

namespace App\Services\Rendering;

use App\DTOs\Docs\EndpointDocumentDto;
use App\DTOs\Docs\RelatedEndpointDto;
use App\DTOs\Docs\TocItemDto;
use App\Enums\PublishStatus;
use App\Models\ApiEndpoint;
use App\Models\ApiEnvironment;
use App\Repositories\Contracts\EnvironmentRepositoryInterface;
use App\Services\Cms\DefaultApiGroupResolver;
use App\Services\Environment\BaseUrlResolver;
use Illuminate\Support\Collection;

class EndpointDocumentBuilder
{
    public function __construct(
        private readonly SectionRenderer $sections,
        private readonly BaseUrlResolver $baseUrls,
        private readonly EnvironmentRepositoryInterface $environments,
    ) {}

    public function build(
        ApiEndpoint $endpoint,
        bool $preview = false,
        ?ApiEnvironment $environment = null,
    ): EndpointDocumentDto {
        $environment ??= $this->environments->default();
        $sections = $this->sections->forEndpoint($endpoint, $environment);

        $toc = collect([
            new TocItemDto('Summary', 'summary'),
        ])->concat(
            $sections->map(fn ($section) => new TocItemDto($section->label, $section->anchor))
        )->concat([
            new TocItemDto('Status', 'meta'),
        ]);

        return new EndpointDocumentDto(
            id: $endpoint->id,
            name: $endpoint->name,
            slug: $endpoint->slug,
            method: $endpoint->method,
            path: $endpoint->path,
            summary: $endpoint->summary,
            status: $endpoint->status,
            publishedAt: $endpoint->published_at?->toDayDateTimeString(),
            versionSlug: $endpoint->version?->slug ?? '',
            versionName: $endpoint->version?->name,
            categoryName: $endpoint->group?->category?->name,
            groupName: app(DefaultApiGroupResolver::class)->isDefault($endpoint->group)
                ? null
                : $endpoint->group?->name,
            baseUrl: $environment
                ? $this->baseUrls->forEndpoint($endpoint, $environment)
                : null,
            environmentName: $environment?->name,
            preview: $preview,
            sections: $sections,
            toc: $toc,
            related: $this->related($endpoint, $preview),
        );
    }

    /**
     * @return Collection<int, RelatedEndpointDto>
     */
    private function related(ApiEndpoint $endpoint, bool $preview): Collection
    {
        return $endpoint->relatedEndpoints
            ->sortBy(fn ($related) => $related->pivot->sort_order ?? 0)
            ->values()
            ->filter(function (ApiEndpoint $related) use ($preview): bool {
                if ($preview) {
                    return true;
                }

                return $related->status instanceof PublishStatus
                    && $related->status->isPubliclyVisible();
            })
            ->map(function (ApiEndpoint $related): RelatedEndpointDto {
                $versionSlug = $related->version?->slug ?? '';

                return new RelatedEndpointDto(
                    name: $related->name,
                    slug: $related->slug,
                    versionSlug: $versionSlug,
                    method: $related->method,
                    path: $related->path,
                    label: $related->pivot->label ?: null,
                    url: route('docs.endpoints.show', [
                        'version' => $versionSlug,
                        'endpoint' => $related->slug,
                    ]),
                );
            });
    }
}
