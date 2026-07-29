<?php

namespace App\Services\Rendering;

use Illuminate\Support\Str;

class MarkdownRenderer
{
    public function toHtml(?string $markdown): string
    {
        if (! filled($markdown)) {
            return '';
        }

        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
