<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deposit extends Model
{
    public const METHOD_ONLINE = 'online';

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    protected $fillable = [
        'user_id',
        'order_id',
        'amount',
        'method',
        'status',
        'gateway_ref',
        'utr',
        'proof_path',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
        'payload',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'payload' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isBankTransfer(): bool
    {
        return $this->method === self::METHOD_BANK_TRANSFER;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
