<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxationOrder extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const DOCUMENTS_PENDING = 'pending';

    public const DOCUMENTS_SUBMITTED = 'submitted';

    public const DOCUMENTS_VERIFIED = 'verified';

    public const DOCUMENTS_APPROVED = 'approved';

    public const DOCUMENTS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'whitelabel_id',
        'taxation_client_id',
        'taxation_service_id',
        'service_name',
        'amount',
        'commission_percentage',
        'commission_amount',
        'whitelabel_commission_amount',
        'status',
        'documents_status',
        'documents_note',
        'documents_reviewed_at',
        'documents_reviewed_by',
        'client_request_id',
        'api_request_id',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'taxation_service_id' => 'integer',
            'amount' => 'decimal:2',
            'commission_percentage' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'whitelabel_commission_amount' => 'decimal:2',
            'documents_reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function whitelabel(): BelongsTo
    {
        return $this->belongsTo(Whitelabel::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(TaxationClient::class, 'taxation_client_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(TaxationService::class, 'taxation_service_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TaxationDocument::class);
    }

    public function documentsReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'documents_reviewed_by');
    }

    public function canReceiveDocuments(): bool
    {
        return ! in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_COMPLETED], true)
            && $this->documents_status !== self::DOCUMENTS_APPROVED;
    }
}
