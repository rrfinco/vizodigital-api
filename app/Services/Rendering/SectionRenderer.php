<?php

namespace App\Services\Rendering;

use App\DTOs\Docs\SectionDto;
use App\Enums\SectionKey;
use App\Models\ApiEndpoint;
use App\Models\ApiEnvironment;
use App\Models\EndpointSection;
use App\Services\Whitelabel\WhitelabelSampleUrlRewriter;
use Illuminate\Support\Collection;

class SectionRenderer
{
    public function __construct(
        private readonly MarkdownRenderer $markdown,
        private readonly WhitelabelSampleUrlRewriter $sampleUrls,
    ) {}

    /**
     * Build visible section DTOs from the endpoint's enabled layout.
     *
     * @return Collection<int, SectionDto>
     */
    public function forEndpoint(ApiEndpoint $endpoint, ?ApiEnvironment $environment = null): Collection
    {
        return $endpoint->sections
            ->filter(fn (EndpointSection $section) => $section->enabled)
            ->sortBy('sort_order')
            ->values()
            ->map(fn (EndpointSection $section) => $this->mapSection($section, $endpoint, $environment))
            ->filter()
            ->values();
    }

    public function render(SectionDto $section): string
    {
        return view($section->component, [
            'section' => $section,
            'label' => $section->label,
            'anchor' => $section->anchor,
            'config' => $section->config,
            ...$section->data,
        ])->render();
    }

    private function mapSection(
        EndpointSection $section,
        ApiEndpoint $endpoint,
        ?ApiEnvironment $environment,
    ): ?SectionDto {
        $key = $section->section_key;

        if (! $key instanceof SectionKey) {
            return null;
        }

        $data = $this->payload($key, $endpoint, $environment);

        if ($data === null) {
            return null;
        }

        return new SectionDto(
            key: $key,
            label: $key->label(),
            component: $key->component(),
            anchor: $key->value,
            config: $section->config ?? [],
            data: $data,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function payload(
        SectionKey $key,
        ApiEndpoint $endpoint,
        ?ApiEnvironment $environment,
    ): ?array {
        return match ($key) {
            SectionKey::Overview => $this->overview($endpoint),
            SectionKey::Headers => $this->collectionPayload('headers', $endpoint->headers),
            SectionKey::Parameters => $this->collectionPayload('parameters', $endpoint->parameters),
            SectionKey::Body => $this->collectionPayload('bodies', $endpoint->requestBodies),
            SectionKey::Responses => $this->collectionPayload('responses', $endpoint->responses),
            SectionKey::Examples => $this->examples($endpoint, $environment),
            SectionKey::Sdk => $this->sdk($endpoint, $environment),
            SectionKey::Errors => $this->collectionPayload('errors', $endpoint->errors),
            SectionKey::Notes => $this->notes($endpoint),
            SectionKey::RateLimits => filled($endpoint->rate_limit)
                ? ['rateLimit' => $endpoint->rate_limit]
                : null,
            SectionKey::Permissions => filled($endpoint->permission_name)
                ? ['permission' => $endpoint->permission_name]
                : null,
            SectionKey::Webhooks => ['message' => 'Webhook details for this endpoint can be documented in the CMS.'],
            SectionKey::TryApi => ['message' => 'Interactive Try API ships in a later module.'],
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function overview(ApiEndpoint $endpoint): ?array
    {
        if (! filled($endpoint->description_md)) {
            return null;
        }

        return [
            'html' => $this->markdown->toHtml($endpoint->description_md),
        ];
    }

    /**
     * @param  Collection<int, mixed>  $items
     * @return array<string, mixed>|null
     */
    private function collectionPayload(string $key, Collection $items): ?array
    {
        if ($items->isEmpty()) {
            return null;
        }

        return [$key => $items];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function examples(ApiEndpoint $endpoint, ?ApiEnvironment $environment): ?array
    {
        $examples = $endpoint->examples;

        if ($environment) {
            $examples = $examples->where('api_environment_id', $environment->id)->values();
        }

        if ($examples->isEmpty()) {
            return null;
        }

        return [
            'examples' => $this->sampleUrls->rewriteExamples($examples),
            'environment' => $environment,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function sdk(ApiEndpoint $endpoint, ?ApiEnvironment $environment): ?array
    {
        $samples = $endpoint->codeSamples;

        if ($environment) {
            $samples = $samples->where('api_environment_id', $environment->id)->values();
        }

        if ($samples->isEmpty()) {
            return null;
        }

        return [
            'samples' => $this->sampleUrls->rewriteCodeSamples($samples),
            'environment' => $environment,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function notes(ApiEndpoint $endpoint): ?array
    {
        if ($endpoint->notes->isEmpty()) {
            return null;
        }

        $notes = $endpoint->notes->map(fn ($note) => [
            'html' => $this->markdown->toHtml($note->body_md),
        ]);

        return ['notes' => $notes];
    }
}
