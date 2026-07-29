<?php

namespace App\Models;

use App\Enums\SnippetLanguage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodeSample extends Model
{
    protected $fillable = [
        'api_endpoint_id',
        'api_environment_id',
        'language',
        'code',
        'is_generated',
        'is_override',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'language' => SnippetLanguage::class,
            'is_generated' => 'boolean',
            'is_override' => 'boolean',
        ];
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(ApiEndpoint::class, 'api_endpoint_id');
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(ApiEnvironment::class, 'api_environment_id');
    }
}
