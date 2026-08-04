<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhitelabelOperatorCommission extends Model
{
    protected $fillable = [
        'whitelabel_id',
        'operator_type',
        'operator_sp_key',
        'commission_percentage',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'commission_percentage' => 'decimal:2',
            'status' => 'boolean',
        ];
    }

    public function whitelabel(): BelongsTo
    {
        return $this->belongsTo(Whitelabel::class);
    }

    /**
     * Resolve WL recharge commission. Missing row => operator default % + active.
     *
     * @return array{commission_percentage: float, status: bool}
     */
    public static function resolveFor(int $whitelabelId, string $operatorType, int $spKey, float $defaultPercentage): array
    {
        $row = static::query()
            ->where('whitelabel_id', $whitelabelId)
            ->where('operator_type', $operatorType)
            ->where('operator_sp_key', $spKey)
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
