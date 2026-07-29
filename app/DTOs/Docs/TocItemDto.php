<?php

namespace App\DTOs\Docs;

readonly class TocItemDto
{
    public function __construct(
        public string $label,
        public string $anchor,
    ) {}
}
