<?php

namespace App\Services\Search;

use App\Enums\PublishStatus;
use App\Enums\SearchDocumentType;
use App\Models\ApiCategory;
use App\Models\ApiEndpoint;
use App\Models\ApiGroup;
use App\Models\ApiVersion;
use App\Models\ChangelogEntry;
use App\Models\DocumentationPage;
use App\Models\Faq;
use App\Repositories\Contracts\SearchRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SearchIndexer
{
    public function __construct(
        private readonly SearchRepositoryInterface $search,
    ) {}

    public function upsert(Model $model): void
    {
        $payload = $this->payload($model);

        if ($payload === null) {
            $this->remove($model);

            return;
        }

        $this->search->upsert($model, $payload);
    }

    public function remove(Model $model): void
    {
        $this->search->deleteBySearchable($model);
    }

    public function reindexAll(): int
    {
        $count = 0;

        foreach ([
            ApiEndpoint::query()->with('version')->cursor(),
            DocumentationPage::query()->with('version')->cursor(),
            ApiCategory::query()->with('version')->cursor(),
            ApiGroup::query()->with('category.version')->cursor(),
            Faq::query()->with('version')->cursor(),
            ChangelogEntry::query()->with('version')->cursor(),
        ] as $cursor) {
            foreach ($cursor as $model) {
                $this->upsert($model);
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return array{
     *     api_version_id: int|null,
     *     type: string,
     *     title: string,
     *     body: string|null,
     *     keywords: string|null,
     *     status: string,
     *     url: string|null
     * }|null
     */
    private function payload(Model $model): ?array
    {
        return match (true) {
            $model instanceof ApiEndpoint => $this->endpoint($model),
            $model instanceof DocumentationPage => $this->page($model),
            $model instanceof ApiCategory => $this->category($model),
            $model instanceof ApiGroup => $this->group($model),
            $model instanceof Faq => $this->faq($model),
            $model instanceof ChangelogEntry => $this->changelog($model),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function endpoint(ApiEndpoint $endpoint): array
    {
        $endpoint->loadMissing('version');
        $versionSlug = $endpoint->version?->slug;
        $status = $endpoint->status instanceof PublishStatus
            ? $endpoint->status
            : PublishStatus::Draft;

        return [
            'api_version_id' => $endpoint->api_version_id,
            'type' => SearchDocumentType::Endpoint->value,
            'title' => $endpoint->name,
            'body' => $this->join([
                $endpoint->summary,
                $this->plain($endpoint->description_md),
            ]),
            'keywords' => $this->join([
                $endpoint->slug,
                $endpoint->method?->value,
                $endpoint->path,
            ]),
            'status' => $status->value,
            'url' => $versionSlug
                ? route('docs.endpoints.show', [
                    'version' => $versionSlug,
                    'endpoint' => $endpoint->slug,
                ])
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function page(DocumentationPage $page): array
    {
        $page->loadMissing('version');
        $versionSlug = $page->version?->slug;
        $status = $page->status instanceof PublishStatus
            ? $page->status
            : PublishStatus::Draft;

        return [
            'api_version_id' => $page->api_version_id,
            'type' => SearchDocumentType::Page->value,
            'title' => $page->title,
            'body' => $this->plain($page->body_md),
            'keywords' => $this->join([
                $page->slug,
                $page->type?->value,
            ]),
            'status' => $status->value,
            'url' => $versionSlug
                ? route('docs.pages.show', [
                    'version' => $versionSlug,
                    'page' => $page->slug,
                ])
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function category(ApiCategory $category): array
    {
        $category->loadMissing('version');
        $versionSlug = $category->version?->slug;
        $status = $category->status instanceof PublishStatus
            ? $category->status
            : PublishStatus::Draft;

        return [
            'api_version_id' => $category->api_version_id,
            'type' => SearchDocumentType::Category->value,
            'title' => $category->name,
            'body' => $category->description,
            'keywords' => $category->slug,
            'status' => $status->value,
            'url' => $versionSlug
                ? route('docs.categories.show', [
                    'version' => $versionSlug,
                    'category' => $category->slug,
                ])
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function group(ApiGroup $group): array
    {
        $group->loadMissing('category.version');
        $versionSlug = $group->category?->version?->slug;
        $status = $group->status instanceof PublishStatus
            ? $group->status
            : PublishStatus::Draft;

        return [
            'api_version_id' => $group->category?->api_version_id,
            'type' => SearchDocumentType::Group->value,
            'title' => $group->name,
            'body' => $group->description,
            'keywords' => $group->slug,
            'status' => $status->value,
            'url' => $versionSlug
                ? route('docs.groups.show', [
                    'version' => $versionSlug,
                    'group' => $group->slug,
                ])
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function faq(Faq $faq): array
    {
        $faq->loadMissing('version');
        $versionSlug = $faq->version?->slug
            ?? $this->defaultVersionSlug();
        $status = $faq->status instanceof PublishStatus
            ? $faq->status
            : PublishStatus::Draft;

        return [
            'api_version_id' => $faq->api_version_id,
            'type' => SearchDocumentType::Faq->value,
            'title' => $faq->question,
            'body' => $this->plain($faq->answer_md),
            'keywords' => $faq->category,
            'status' => $status->value,
            'url' => $versionSlug
                ? route('docs.faqs.index', ['version' => $versionSlug]).'#faq-'.$faq->id
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function changelog(ChangelogEntry $entry): array
    {
        $entry->loadMissing('version');
        $versionSlug = $entry->version?->slug;
        $status = $entry->status instanceof PublishStatus
            ? $entry->status
            : PublishStatus::Draft;

        return [
            'api_version_id' => $entry->api_version_id,
            'type' => SearchDocumentType::Changelog->value,
            'title' => $entry->title,
            'body' => $this->plain($entry->body_md),
            'keywords' => $entry->slug,
            'status' => $status->value,
            'url' => $versionSlug
                ? route('docs.changelog.show', [
                    'version' => $versionSlug,
                    'entry' => $entry->slug,
                ])
                : null,
        ];
    }

    private function defaultVersionSlug(): ?string
    {
        return ApiVersion::query()
            ->where('is_default', true)
            ->value('slug')
            ?? ApiVersion::query()->published()->orderBy('sort_order')->value('slug');
    }

    private function plain(?string $markdown): ?string
    {
        if (! filled($markdown)) {
            return null;
        }

        $html = Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');

        return $text !== '' ? $text : null;
    }

    /**
     * @param  list<string|null>  $parts
     */
    private function join(array $parts): ?string
    {
        $value = collect($parts)
            ->filter(fn ($part) => filled($part))
            ->implode(' ');

        return $value !== '' ? $value : null;
    }
}
