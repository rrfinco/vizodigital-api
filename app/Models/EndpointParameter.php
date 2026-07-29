<?php

namespace App\Models;

use App\Enums\ParameterLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EndpointParameter extends Model
{
    protected $fillable = [
        'api_endpoint_id',
        'location',
        'name',
        'type',
        'required',
        'description',
        'example',
        'schema',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'location' => ParameterLocation::class,
            'required' => 'boolean',
            'schema' => 'array',
        ];
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(ApiEndpoint::class, 'api_endpoint_id');
    }
}
