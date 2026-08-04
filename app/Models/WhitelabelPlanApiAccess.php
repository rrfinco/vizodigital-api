<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhitelabelPlanApiAccess extends Model
{
    protected $table = 'whitelabel_plan_api_access';

    protected $fillable = [
        'whitelabel_id',
        'service',
        'status',
        'per_call_fee',
    ];

    protected $casts = [
        'status' => 'boolean',
        'per_call_fee' => 'decimal:2',
    ];

    public function whitelabel(): BelongsTo
    {
        return $this->belongsTo(Whitelabel::class);
    }

    public function isActive(): bool
    {
        return (bool) $this->status;
    }

    /**
     * @return array{per_call_fee: float, status: bool}|null
     */
    public static function resolveFor(int $whitelabelId, string $service): ?array
    {
        $row = static::query()
            ->where('whitelabel_id', $whitelabelId)
            ->where('service', $service)
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'per_call_fee' => (float) $row->per_call_fee,
            'status' => (bool) $row->status,
        ];
    }
}
