<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhitelabelBillOperatorCommission extends Model
{
    public const TYPE_PERCENTAGE = 'percentage';

    public const TYPE_FLAT = 'flat';

    protected $fillable = [
        'whitelabel_id',
        'opcode',
        'commission_type',
        'commission_value',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'commission_value' => 'decimal:2',
            'status' => 'boolean',
        ];
    }

    public function whitelabel(): BelongsTo
    {
        return $this->belongsTo(Whitelabel::class);
    }

    /**
     * Missing row => active with 0 commission.
     *
     * @return array{commission_type: string, commission_value: float, status: bool}
     */
    public static function resolveFor(int $whitelabelId, string $opcode): array
    {
        $row = static::query()
            ->where('whitelabel_id', $whitelabelId)
            ->where('opcode', $opcode)
            ->first();

        if (! $row) {
            return [
                'commission_type' => self::TYPE_PERCENTAGE,
                'commission_value' => 0.0,
                'status' => true,
            ];
        }

        return [
            'commission_type' => in_array($row->commission_type, [self::TYPE_PERCENTAGE, self::TYPE_FLAT], true)
                ? $row->commission_type
                : self::TYPE_PERCENTAGE,
            'commission_value' => (float) $row->commission_value,
            'status' => (bool) $row->status,
        ];
    }

    public static function calculateAmount(string $type, float $value, float $billAmount): float
    {
        return UserBillOperatorCommission::calculateAmount($type, $value, $billAmount);
    }
}
