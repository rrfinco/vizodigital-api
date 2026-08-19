<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxationClient extends Model
{
    protected $fillable = [
        'user_id',
        'whitelabel_id',
        'client_request_id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'pan',
        'aadhaar',
        'residence_address',
        'residence_city',
        'residence_pincode',
        'residence_state',
        'office_address',
        'office_city',
        'office_pincode',
        'office_state',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function whitelabel(): BelongsTo
    {
        return $this->belongsTo(Whitelabel::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(TaxationOrder::class);
    }

    public function fullName(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])));
    }
}
