<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhitelabelTaxationCommission extends Model
{
    protected $fillable = [
        'whitelabel_id',
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

    public function whitelabel(): BelongsTo
    {
        return $this->belongsTo(Whitelabel::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(TaxationService::class, 'taxation_service_id');
    }

    /**
     * @return array{commission_percentage: float, status: bool}
     */
    public static function resolveFor(int $whitelabelId, int $serviceId, float $defaultPercentage): array
    {
        $row = static::query()
            ->where('whitelabel_id', $whitelabelId)
            ->where('taxation_service_id', $serviceId)
            ->first();

        if (! $row) {
            return [
                'commission_percentage' => $defaultPercentage,
                'status' => true,
            ];
        }

        return [
            'commission_percentage' => (float) $row->commission_percentage,
            'status' => (bool) $row->status,
        ];
    }
}
