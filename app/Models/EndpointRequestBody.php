<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EndpointRequestBody extends Model
{
    protected $fillable = [
        'api_endpoint_id',
        'content_type',
        'description',
        'schema',
        'example',
        'required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'example' => 'array',
            'required' => 'boolean',
        ];
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(ApiEndpoint::class, 'api_endpoint_id');
    }
}
