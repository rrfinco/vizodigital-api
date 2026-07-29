<?php

namespace App\DTOs\Docs;

use Illuminate\Support\Collection;

readonly class NavigationNodeDto
{
    /**
     * @param  Collection<int, NavigationNodeDto>  $children
     */
    public function __construct(
        public string $label,
        public ?string $href,
        public bool $active,
        public bool $external = false,
        public ?string $badge = null,
        public Collection $children = new Collection,
    ) {}
}
