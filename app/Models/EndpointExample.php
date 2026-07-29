<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EndpointExample extends Model
{
    protected $fillable = [
        'api_endpoint_id',
        'api_environment_id',
        'title',
        'request',
        'response',
        'response_status',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'request' => 'array',
            'response' => 'array',
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
