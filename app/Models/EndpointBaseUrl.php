<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EndpointBaseUrl extends Model
{
    protected $fillable = [
        'api_environment_id',
        'urlable_type',
        'urlable_id',
        'base_url',
    ];

    public function environment(): BelongsTo
    {
        return $this->belongsTo(ApiEnvironment::class, 'api_environment_id');
    }

    public function urlable(): MorphTo
    {
        return $this->morphTo();
    }
}
