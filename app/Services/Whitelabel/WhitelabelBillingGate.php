<?php

namespace App\Services\Whitelabel;

use App\Exceptions\WhitelabelUnavailableException;
use App\Models\User;
use App\Models\Whitelabel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class WhitelabelBillingGate
{
    /**
     * Lock the developer's whitelabel (if any) and assert it can cover $amount.
     * Must be called inside a DB transaction. Always lock WL before User.
     */
    public function lockForDebit(User $user, float $amount): ?Whitelabel
    {
        if (! $user->whitelabel_id) {
            return null;
        }

        /** @var Whitelabel|null $wl */
        $wl = Whitelabel::query()->lockForUpdate()->find($user->whitelabel_id);

        if (! $wl) {
            Log::error('wl_missing', ['whitelabel_id' => $user->whitelabel_id, 'user_id' => $user->id]);
            throw new WhitelabelUnavailableException(
                WhitelabelUnavailableException::REASON_SUSPENDED,
                $user->whitelabel_id
            );
        }

        $this->assertUsable($wl, $amount);

        return $wl;
    }

    /**
     * Lock WL row for refund/commission paths (no balance assert).
     */
    public function lockForUpdate(User $user): ?Whitelabel
    {
        if (! $user->whitelabel_id) {
            return null;
        }

        return Whitelabel::query()->lockForUpdate()->find($user->whitelabel_id);
    }

    public function assertUsable(Whitelabel $wl, float $amount): void
    {
        if (! $wl->isActive()) {
            Log::warning('wl_suspended', [
                'whitelabel_id' => $wl->id,
                'amount' => $amount,
            ]);

            throw new WhitelabelUnavailableException(
                WhitelabelUnavailableException::REASON_SUSPENDED,
                $wl->id
            );
        }

        if ($amount > 0 && (float) $wl->wallet_balance < $amount) {
            Log::warning('wl_float_exhausted', [
                'whitelabel_id' => $wl->id,
                'required' => $amount,
                'available' => (float) $wl->wallet_balance,
            ]);

            throw new WhitelabelUnavailableException(
                WhitelabelUnavailableException::REASON_FLOAT_EXHAUSTED,
                $wl->id
            );
        }
    }

    public function debit(?Whitelabel $wl, float $amount, string $description, ?Model $reference = null): void
    {
        if (! $wl || $amount <= 0) {
            return;
        }

        $wl->debitWallet($amount, $description, $reference);
    }

    public function refund(?Whitelabel $wl, float $amount, string $description, ?Model $reference = null): void
    {
        if (! $wl || $amount <= 0) {
            return;
        }

        $wl->creditWallet($amount, $description, $reference);
    }

    public function creditCommission(?Whitelabel $wl, float $amount, string $description, ?Model $reference = null): void
    {
        if (! $wl || $amount <= 0) {
            return;
        }

        $wl->creditWallet($amount, $description, $reference);
    }
}
