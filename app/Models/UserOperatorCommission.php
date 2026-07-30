<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserOperatorCommission extends Model
{
    protected $fillable = [
        'user_id',
        'operator_type',
        'operator_sp_key',
        'commission_percentage',
        'status',
    ];

    protected $casts = [
        'commission_percentage' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

