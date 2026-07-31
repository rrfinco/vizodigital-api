<?php

namespace App\Http\Controllers\Web\Docs;

use App\Enums\SearchDocumentType;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\SearchRepositoryInterface;
use App\Services\Portal\PortalContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    public function __construct(
        private readonly SearchRepositoryInterface $search,
        private readonly PortalContext $portal,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'version' => ['nullable', 'string', 'max:50'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = trim((string) ($validated['q'] ?? ''));
        $minChars = (int) config('portal.search.min_chars', 2);

        if (mb_strlen($query) < $minChars) {
            return response()->json([
                'query' => $query,
                'results' => [],
            ]);
        }

        $versionSlug = $validated['version']
            ?? $this->portal->version()?->slug;

        $limit = $validated['limit']
            ?? (int) config('portal.search.limit', 12);

        $results = $this->search
            ->search($query, $versionSlug, $limit)
            ->map(function ($hit) use ($query): array {
                $type = SearchDocumentType::tryFrom($hit->type);

                return [
                    'title' => $hit->title,
                    'type' => $hit->type,
                    'type_label' => $type?->label() ?? Str::headline($hit->type),
                    'url' => $this->publicUrl($hit->url),
                    'snippet' => $this->snippet($hit->body, $query),
                ];
            })
            ->values();

        return response()->json([
            'query' => $query,
            'version' => $versionSlug,
            'results' => $results,
        ]);
    }

    /**
     * Normalize legacy absolute search URLs (often indexed with local APP_URL).
     */
    private function publicUrl(?string $url): ?string
    {
        if (! filled($url)) {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $path.$query.$fragment;
    }

    private function snippet(?string $body, string $query): ?string
    {
        if (! filled($body)) {
            return null;
        }

        $needle = mb_strtolower($query);
        $haystack = mb_strtolower($body);
        $pos = mb_strpos($haystack, $needle);

        if ($pos === false) {
            return Str::limit($body, 120);
        }

        $start = max(0, $pos - 40);
        $excerpt = mb_substr($body, $start, 140);

        return ($start > 0 ? '…' : '').trim($excerpt).(mb_strlen($body) > $start + 140 ? '…' : '');
    }
}
