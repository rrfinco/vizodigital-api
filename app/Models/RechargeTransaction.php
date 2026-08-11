<?php

namespace App\Models;

use App\Enums\RechargeProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RechargeTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'client_request_id',
        'api_request_id',
        'operator_sp_key',
        'operator_type',
        'account_number',
        'circle',
        'amount',
        'commission_percentage',
        'commission_amount',
        'net_amount',
        'status',
        'rpid',
        'opid',
        'error_code',
        'error_message',
    ];

    protected $casts = [
        'operator_sp_key' => 'integer',
        'provider' => RechargeProvider::class,
        'amount' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
