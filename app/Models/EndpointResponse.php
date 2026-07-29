<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EndpointResponse extends Model
{
    protected $fillable = [
        'api_endpoint_id',
        'status_code',
        'description',
        'content_type',
        'schema',
        'example',
        'is_default',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'example' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(ApiEndpoint::class, 'api_endpoint_id');
    }
}
