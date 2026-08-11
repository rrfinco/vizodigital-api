<?php

namespace App\Models;

use App\Enums\RechargeProvider;
use App\Enums\WhitelabelDomainRole;
use App\Enums\WhitelabelStatus;
use Database\Factories\WhitelabelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Whitelabel extends Model
{
    /** @use HasFactory<WhitelabelFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'recharge_provider',
        'wallet_balance',
        'owner_user_id',
        'brand_name',
        'logo_path',
        'primary_color',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => WhitelabelStatus::class,
            'recharge_provider' => RechargeProvider::class,
            'wallet_balance' => 'decimal:4',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function domains(): HasMany
    {
        return $this->hasMany(WhitelabelDomain::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WhitelabelWalletTransaction::class);
    }

    public function floatRequests(): HasMany
    {
        return $this->hasMany(WhitelabelFloatRequest::class);
    }

    public function operatorCommissions(): HasMany
    {
        return $this->hasMany(WhitelabelOperatorCommission::class);
    }

    public function billOperatorCommissions(): HasMany
    {
        return $this->hasMany(WhitelabelBillOperatorCommission::class);
    }

    public function planApiAccess(): HasMany
    {
        return $this->hasMany(WhitelabelPlanApiAccess::class);
    }

    public function isActive(): bool
    {
        return ($this->status ?? WhitelabelStatus::Active)->isUsable();
    }

    public function domainForRole(WhitelabelDomainRole $role): ?WhitelabelDomain
    {
        /** @var Collection<int, WhitelabelDomain> $domains */
        $domains = $this->relationLoaded('domains')
            ? $this->domains
            : $this->domains()->get();

        $matching = $domains->filter(
            fn (WhitelabelDomain $domain): bool => ($domain->role ?? WhitelabelDomainRole::Portal) === $role
        );

        return $matching->firstWhere('is_primary', true) ?? $matching->first();
    }

    public function baseUrlForRole(WhitelabelDomainRole $role): ?string
    {
        $domain = $this->domainForRole($role);

        return $domain?->baseUrl();
    }

    public function debitWallet(float $amount, string $description, ?Model $reference = null): WhitelabelWalletTransaction
    {
        $amount = abs($amount);
        if ((float) $this->wallet_balance < $amount) {
            throw new \RuntimeException('Insufficient whitelabel float balance.');
        }

        $balanceBefore = (float) $this->wallet_balance;
        $this->wallet_balance = $balanceBefore - $amount;
        $this->save();

        return $this->walletTransactions()->create([
            'amount' => -$amount,
            'type' => 'debit',
            'description' => $description,
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference?->getKey(),
            'balance_before' => $balanceBefore,
            'balance_after' => (float) $this->wallet_balance,
        ]);
    }

    public function creditWallet(float $amount, string $description, ?Model $reference = null): WhitelabelWalletTransaction
    {
        $amount = abs($amount);
        $balanceBefore = (float) $this->wallet_balance;
        $this->wallet_balance = $balanceBefore + $amount;
        $this->save();

        return $this->walletTransactions()->create([
            'amount' => $amount,
            'type' => 'credit',
            'description' => $description,
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference?->getKey(),
            'balance_before' => $balanceBefore,
            'balance_after' => (float) $this->wallet_balance,
        ]);
    }
}
