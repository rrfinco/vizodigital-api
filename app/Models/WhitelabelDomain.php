<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhitelabelDomain extends Model
{
    protected $fillable = [
        'whitelabel_id',
        'host',
        'is_primary',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function whitelabel(): BelongsTo
    {
        return $this->belongsTo(Whitelabel::class);
    }

    public function setHostAttribute(string $value): void
    {
        $this->attributes['host'] = strtolower(trim($value));
    }
}
