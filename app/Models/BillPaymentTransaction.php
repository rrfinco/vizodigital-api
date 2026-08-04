<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillPaymentTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'order_id',
        'mobile',
        'card',
        'opcode',
        'amount',
        'commission_type',
        'commission_value',
        'commission_amount',
        'fetch_id',
        'pan',
        'status',
        'provider_txid',
        'utr',
        'error_message',
        'request_payload',
        'response_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'commission_value' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
