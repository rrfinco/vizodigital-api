<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPlanApiAccess extends Model
{
    protected $table = 'user_plan_api_access';

    protected $fillable = [
        'user_id',
        'service',
        'status',
        'per_call_fee',
    ];

    protected $casts = [
        'status' => 'boolean',
        'per_call_fee' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return (bool) $this->status;
    }
}
