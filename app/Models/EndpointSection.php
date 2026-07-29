<?php

namespace App\Models;

use App\Enums\SectionKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EndpointSection extends Model
{
    protected $fillable = [
        'api_endpoint_id',
        'section_key',
        'enabled',
        'sort_order',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'section_key' => SectionKey::class,
            'enabled' => 'boolean',
            'config' => 'array',
        ];
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(ApiEndpoint::class, 'api_endpoint_id');
    }
}
