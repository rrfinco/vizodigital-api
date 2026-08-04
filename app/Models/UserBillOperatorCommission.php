<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBillOperatorCommission extends Model
{
    public const TYPE_PERCENTAGE = 'percentage';

    public const TYPE_FLAT = 'flat';

    protected $fillable = [
        'user_id',
        'opcode',
        'commission_type',
        'commission_value',
        'status',
    ];

    protected $casts = [
        'commission_value' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Resolve commission config for a user + opcode.
     * Missing row => active with 0% commission.
     *
     * @return array{commission_type: string, commission_value: float, status: bool}
     */
    public static function resolveFor(int $userId, string $opcode): array
    {
        $row = static::query()
            ->where('user_id', $userId)
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
        if ($type === self::TYPE_FLAT) {
            return round(max(0, $value), 2);
        }

        return round(($billAmount * max(0, $value)) / 100, 2);
    }
}
