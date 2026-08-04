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
        'whitelabel_id',
        'wallet_balance',
        'onboarding_status',
        'kyc_token',
        'kyc_token_expires_at',
        'kyc_submitted_at',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'earning_balance',
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
            'wallet_balance' => 'decimal:4',
            'earning_balance' => 'decimal:4',
        ];
    }

    public function whitelabel(): BelongsTo
    {
        return $this->belongsTo(Whitelabel::class);
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

    public function rechargeTransactions(): HasMany
    {
        return $this->hasMany(RechargeTransaction::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function activeSubscription(): ?UserSubscription
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->latest('ends_at')
            ->first();
    }

    public function debitWallet(float $amount, string $description, ?\Illuminate\Database\Eloquent\Model $reference = null): WalletTransaction
    {
        $amount = abs($amount);
        if ($this->wallet_balance < $amount) {
            throw new \Exception('Insufficient wallet balance. Please recharge your wallet.');
        }

        $balanceBefore = (float) $this->wallet_balance;
        $this->wallet_balance -= $amount;
        $this->save();

        return $this->walletTransactions()->create([
            'amount' => -$amount,
            'type' => 'debit',
            'description' => $description,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference ? $reference->getKey() : null,
            'balance_before' => $balanceBefore,
            'balance_after' => (float) $this->wallet_balance,
        ]);
    }

    public function creditWallet(float $amount, string $description, ?\Illuminate\Database\Eloquent\Model $reference = null): WalletTransaction
    {
        $amount = abs($amount);
        $balanceBefore = (float) $this->wallet_balance;
        $this->wallet_balance += $amount;
        $this->save();

        return $this->walletTransactions()->create([
            'amount' => $amount,
            'type' => 'credit',
            'description' => $description,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference ? $reference->getKey() : null,
            'balance_before' => $balanceBefore,
            'balance_after' => (float) $this->wallet_balance,
        ]);
    }

    public function addEarning(float $amount): void
    {
        $amount = abs($amount);
        $this->earning_balance += $amount;
        $this->save();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->isStaff(),
            'partner' => $this->isWhitelabelPartner() && $this->belongsToResolvedWhitelabelHost(),
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

    public function isWhitelabelPartner(): bool
    {
        return $this->hasRole('whitelabel') && $this->whitelabel_id !== null;
    }

    /**
     * Partner may only use the panel on their own white-label domain.
     */
    public function belongsToResolvedWhitelabelHost(): bool
    {
        $hostWlId = app(\App\Services\Whitelabel\WhitelabelContext::class)->id();

        return $hostWlId !== null && (int) $hostWlId === (int) $this->whitelabel_id;
    }

    public function isDeveloper(): bool
    {
        return $this->hasRole('developer');
    }

    public function belongsToWhitelabel(): bool
    {
        return $this->whitelabel_id !== null;
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
