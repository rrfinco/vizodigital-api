<?php

namespace App\Services\Subscription;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\Whitelabel\WhitelabelBillingGate;
use Illuminate\Support\Facades\DB;

class SubscriptionPurchaseService
{
    public function __construct(
        private readonly WhitelabelBillingGate $whitelabelBillingGate,
    ) {}

    public function purchase(User $user, SubscriptionPlan $plan): UserSubscription
    {
        if (! $plan->is_active) {
            throw new \Exception('This subscription plan is not available.');
        }

        $price = (float) $plan->price;

        return DB::transaction(function () use ($user, $plan, $price): UserSubscription {
            $wl = $this->whitelabelBillingGate->lockForDebit($user, $price);

            /** @var User $lockedUser */
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            /** @var SubscriptionPlan $lockedPlan */
            $lockedPlan = SubscriptionPlan::query()->lockForUpdate()->findOrFail($plan->id);

            if (! $lockedPlan->is_active) {
                throw new \Exception('This subscription plan is not available.');
            }

            $activeSamePlan = UserSubscription::query()
                ->where('user_id', $lockedUser->id)
                ->where('subscription_plan_id', $lockedPlan->id)
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->lockForUpdate()
                ->first();

            if ($activeSamePlan) {
                throw new \Exception('You already have an active subscription for this plan.');
            }

            if ($price > 0 && (float) $lockedUser->wallet_balance < $price) {
                throw new \Exception(
                    'Insufficient wallet balance. Available: ₹'
                    .number_format((float) $lockedUser->wallet_balance, 2)
                    .'. Required: ₹'
                    .number_format($price, 2)
                    .'.'
                );
            }

            UserSubscription::query()
                ->where('user_id', $lockedUser->id)
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->update(['status' => 'replaced']);

            $startsAt = now();
            $endsAt = now()->addDays(max(1, (int) $lockedPlan->duration_days));

            $subscription = UserSubscription::create([
                'user_id' => $lockedUser->id,
                'subscription_plan_id' => $lockedPlan->id,
                'amount_paid' => $price,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'active',
            ]);

            if ($price > 0) {
                $lockedUser->debitWallet(
                    $price,
                    "Subscription purchase: {$lockedPlan->name} ({$lockedPlan->duration_days} days)",
                    $subscription
                );
                $this->whitelabelBillingGate->debit(
                    $wl,
                    $price,
                    "Subscription purchase: {$lockedPlan->name} ({$lockedPlan->duration_days} days)",
                    $subscription
                );
            }

            return $subscription->fresh(['plan']);
        });
    }
}
