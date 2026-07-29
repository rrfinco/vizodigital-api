<?php

namespace App\DTOs\Docs;

use App\Enums\HttpMethod;

readonly class RelatedEndpointDto
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $versionSlug,
        public ?HttpMethod $method,
        public string $path,
        public ?string $label,
        public string $url,
    ) {}
}
