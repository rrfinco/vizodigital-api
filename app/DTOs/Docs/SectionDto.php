<?php

namespace App\DTOs\Docs;

use App\Enums\SectionKey;

readonly class SectionDto
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public SectionKey $key,
        public string $label,
        public string $component,
        public string $anchor,
        public array $config = [],
        public array $data = [],
    ) {}
}
