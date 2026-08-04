<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhitelabelFloatRequest extends Model
{
    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'whitelabel_id',
        'requested_by',
        'amount',
        'method',
        'status',
        'utr',
        'proof_path',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'payload' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function whitelabel(): BelongsTo
    {
        return $this->belongsTo(Whitelabel::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
