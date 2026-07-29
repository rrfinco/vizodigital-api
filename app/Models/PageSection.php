<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSection extends Model
{
    protected $fillable = [
        'documentation_page_id',
        'section_key',
        'title',
        'body_md',
        'config',
        'enabled',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'enabled' => 'boolean',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(DocumentationPage::class, 'documentation_page_id');
    }
}
