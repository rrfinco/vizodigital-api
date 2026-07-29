<?php

namespace App\DTOs\Docs;

use App\Enums\DocPageType;
use App\Enums\PublishStatus;
use Illuminate\Support\Collection;

readonly class DocumentationPageDto
{
    /**
     * @param  Collection<int, PageBlockDto>  $blocks
     * @param  Collection<int, TocItemDto>  $toc
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public ?DocPageType $type,
        public ?PublishStatus $status,
        public string $versionSlug,
        public ?string $versionName,
        public ?string $bodyHtml,
        public bool $preview,
        public Collection $blocks,
        public Collection $toc,
    ) {}
}
