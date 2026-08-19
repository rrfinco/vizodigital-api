<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTaxationCommission extends Model
{
    protected $fillable = [
        'user_id',
        'taxation_service_id',
        'commission_percentage',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'taxation_service_id' => 'integer',
            'commission_percentage' => 'decimal:2',
            'status' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(TaxationService::class, 'taxation_service_id');
    }
}
