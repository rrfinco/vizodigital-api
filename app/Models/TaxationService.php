<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxationService extends Model
{
    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'taxation_category_id',
        'name',
        'price',
        'default_commission_percentage',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'price' => 'decimal:2',
            'default_commission_percentage' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TaxationCategory::class, 'taxation_category_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(TaxationOrder::class);
    }
}
