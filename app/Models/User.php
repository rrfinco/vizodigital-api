<?php

namespace App\Models;

use App\Enums\OnboardingStatus;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'company_name',
        'phone',
        'onboarding_status',
        'kyc_token',
        'kyc_token_expires_at',
        'kyc_submitted_at',
        'approved_at',
        'approved_by',
        'rejection_reason',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'kyc_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'onboarding_status' => OnboardingStatus::class,
            'kyc_token_expires_at' => 'datetime',
            'kyc_submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function apiCredentials(): HasMany
    {
        return $this->hasMany(ApiCredential::class);
    }

    public function kycDocuments(): HasMany
    {
        return $this->hasMany(KycDocument::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'approved_by');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->isStaff(),
            'user' => $this->isDeveloper() && $this->isOnboardingApproved(),
            default => false,
        };
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole([
            'super_admin',
            'admin',
            'editor',
            'viewer',
        ]);
    }

    public function isDeveloper(): bool
    {
        return $this->hasRole('developer');
    }

    public function isOnboardingApproved(): bool
    {
        if ($this->isStaff() && ! $this->isDeveloper()) {
            return true;
        }

        $status = $this->onboarding_status ?? OnboardingStatus::Approved;

        return $status->canLogin();
    }

    public function hasValidKycToken(): bool
    {
        return filled($this->kyc_token)
            && $this->kyc_token_expires_at !== null
            && $this->kyc_token_expires_at->isFuture();
    }
}
