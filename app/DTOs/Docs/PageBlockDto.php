<?php

namespace App\DTOs\Docs;

readonly class PageBlockDto
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public string $key,
        public string $anchor,
        public ?string $title,
        public string $bodyHtml,
        public array $config = [],
    ) {}
}
