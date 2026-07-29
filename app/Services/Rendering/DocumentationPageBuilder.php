<?php

namespace App\Services\Rendering;

use App\DTOs\Docs\DocumentationPageDto;
use App\DTOs\Docs\PageBlockDto;
use App\DTOs\Docs\TocItemDto;
use App\Models\DocumentationPage;
use App\Models\PageSection;
use Illuminate\Support\Str;

class DocumentationPageBuilder
{
    public function __construct(
        private readonly MarkdownRenderer $markdown,
    ) {}

    public function build(DocumentationPage $page, bool $preview = false): DocumentationPageDto
    {
        $blocks = $page->sections
            ->filter(fn (PageSection $section) => $section->enabled)
            ->sortBy('sort_order')
            ->values()
            ->map(function (PageSection $section, int $index): PageBlockDto {
                $title = $section->title ?: Str::headline(str_replace('_', ' ', $section->section_key));
                $anchor = Str::slug($section->section_key.'-'.$index) ?: 'section-'.$index;

                return new PageBlockDto(
                    key: $section->section_key,
                    anchor: $anchor,
                    title: $title,
                    bodyHtml: $this->markdown->toHtml($section->body_md),
                    config: $section->config ?? [],
                );
            });

        $toc = collect();

        if (filled($page->body_md)) {
            $toc->push(new TocItemDto('Overview', 'overview'));
        }

        foreach ($blocks as $block) {
            $toc->push(new TocItemDto($block->title ?? 'Section', $block->anchor));
        }

        return new DocumentationPageDto(
            id: $page->id,
            title: $page->title,
            slug: $page->slug,
            type: $page->type,
            status: $page->status,
            versionSlug: $page->version?->slug ?? '',
            versionName: $page->version?->name,
            bodyHtml: $this->markdown->toHtml($page->body_md),
            preview: $preview,
            blocks: $blocks,
            toc: $toc,
        );
    }
}
