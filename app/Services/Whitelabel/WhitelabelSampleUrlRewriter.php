<?php

namespace App\Services\Whitelabel;

use App\Enums\EnvironmentSlug;
use App\Models\ApiEnvironment;
use App\Models\CodeSample;
use App\Models\EndpointExample;
use App\Models\Whitelabel;
use Illuminate\Support\Collection;

class WhitelabelSampleUrlRewriter
{
    /** @var array<string, string>|null */
    private ?array $cachedReplacements = null;

    private ?int $cachedWhitelabelId = null;

    public function __construct(
        private readonly WhitelabelContext $context,
        private readonly WhitelabelEnvironmentUrls $urls,
    ) {}

    /**
     * Map platform base URLs → partner base URLs for the active (or given) white-label.
     *
     * @return array<string, string> longest keys first
     */
    public function replacements(?Whitelabel $whitelabel = null): array
    {
        $whitelabel ??= $this->context->whitelabel();
        if (! $whitelabel) {
            return [];
        }

        if ($this->cachedReplacements !== null && $this->cachedWhitelabelId === $whitelabel->id) {
            return $this->cachedReplacements;
        }

        if (! $whitelabel->relationLoaded('domains')) {
            $whitelabel->load('domains');
        }

        $map = [];

        $environments = ApiEnvironment::query()
            ->whereIn('slug', [EnvironmentSlug::Uat->value, EnvironmentSlug::Production->value])
            ->get();

        foreach ($environments as $environment) {
            $slug = $environment->slug instanceof \BackedEnum
                ? $environment->slug->value
                : (string) $environment->slug;

            $partner = $this->urls->forWhitelabel($whitelabel, $slug);
            if ($partner === null) {
                continue;
            }

            $partner = rtrim($partner, '/');
            $candidates = array_filter([
                rtrim((string) $environment->getRawOriginal('base_url') ?: $environment->base_url, '/'),
                rtrim((string) config("portal.environments.{$slug}.base_url"), '/'),
            ]);

            foreach ($candidates as $platform) {
                if ($platform !== '' && $platform !== $partner) {
                    $map[$platform] = $partner;
                }
            }
        }

        uksort($map, fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        $this->cachedWhitelabelId = $whitelabel->id;
        $this->cachedReplacements = $map;

        return $map;
    }

    public function rewriteString(?string $value, ?Whitelabel $whitelabel = null): string
    {
        $value = (string) $value;
        if ($value === '') {
            return $value;
        }

        foreach ($this->replacements($whitelabel) as $from => $to) {
            if ($from === '') {
                continue;
            }

            $value = str_replace(
                [$from.'/', $from],
                [$to.'/', $to],
                $value
            );
        }

        return $value;
    }

    public function rewriteMixed(mixed $value, ?Whitelabel $whitelabel = null): mixed
    {
        if (is_string($value)) {
            return $this->rewriteString($value, $whitelabel);
        }

        if (is_array($value)) {
            $out = [];
            foreach ($value as $key => $item) {
                $out[$key] = $this->rewriteMixed($item, $whitelabel);
            }

            return $out;
        }

        return $value;
    }

    public function rewriteCodeSample(CodeSample $sample, ?Whitelabel $whitelabel = null): CodeSample
    {
        if ($this->replacements($whitelabel) === []) {
            return $sample;
        }

        $copy = clone $sample;
        $copy->setAttribute('code', $this->rewriteString((string) $sample->code, $whitelabel));

        return $copy;
    }

    public function rewriteExample(EndpointExample $example, ?Whitelabel $whitelabel = null): EndpointExample
    {
        if ($this->replacements($whitelabel) === []) {
            return $example;
        }

        $copy = clone $example;
        $copy->setAttribute('request', $this->rewriteMixed($example->request, $whitelabel));
        $copy->setAttribute('response', $this->rewriteMixed($example->response, $whitelabel));
        if (filled($example->description)) {
            $copy->setAttribute('description', $this->rewriteString((string) $example->description, $whitelabel));
        }

        return $copy;
    }

    /**
     * @param  Collection<int, CodeSample>  $samples
     * @return Collection<int, CodeSample>
     */
    public function rewriteCodeSamples(Collection $samples, ?Whitelabel $whitelabel = null): Collection
    {
        return $samples
            ->map(fn (CodeSample $sample): CodeSample => $this->rewriteCodeSample($sample, $whitelabel))
            ->values();
    }

    /**
     * @param  Collection<int, EndpointExample>  $examples
     * @return Collection<int, EndpointExample>
     */
    public function rewriteExamples(Collection $examples, ?Whitelabel $whitelabel = null): Collection
    {
        return $examples
            ->map(fn (EndpointExample $example): EndpointExample => $this->rewriteExample($example, $whitelabel))
            ->values();
    }
}
