<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadStatusSnapshot extends Model
{
    protected $fillable = [
        'user_id',
        'lead_code',
        'last_status',
        'commissioned_at',
    ];

    protected function casts(): array
    {
        return [
            'commissioned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
