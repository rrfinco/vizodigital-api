<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaxationDocument extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    /**
     * @var array<string, string>
     */
    public const TYPES = [
        'pan_card' => 'PAN card',
        'aadhaar_card' => 'Aadhaar card',
        'photograph' => 'Photograph',
        'address_proof' => 'Address proof',
        'bank_statement' => 'Bank statement',
        'gst_certificate' => 'GST certificate',
        'incorporation_certificate' => 'Incorporation certificate',
        'other' => 'Other',
    ];

    protected $fillable = [
        'taxation_order_id',
        'document_type',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
        'status',
        'rejection_reason',
        'reviewed_at',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(TaxationOrder::class, 'taxation_order_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->document_type] ?? str_replace('_', ' ', $this->document_type);
    }

    public function downloadResponse(): StreamedResponse
    {
        return Storage::disk($this->disk)->download($this->path, $this->original_name);
    }
}
