<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EndpointError extends Model
{
    protected $fillable = [
        'api_endpoint_id',
        'error_code',
        'status_code',
        'message',
        'description',
        'example',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'example' => 'array',
        ];
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(ApiEndpoint::class, 'api_endpoint_id');
    }
}
