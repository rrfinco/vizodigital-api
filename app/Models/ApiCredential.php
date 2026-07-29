<?php

namespace App\Models;

use App\Enums\CredentialStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiCredential extends Model
{
    protected $fillable = [
        'user_id',
        'api_environment_id',
        'client_id',
        'api_secret',
        'merchant_id',
        'webhook_secret',
        'status',
        'notes',
    ];

    protected $hidden = [
        'api_secret',
        'webhook_secret',
    ];

    protected function casts(): array
    {
        return [
            'status' => CredentialStatus::class,
            'api_secret' => 'encrypted',
            'webhook_secret' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(ApiEnvironment::class, 'api_environment_id');
    }
}
