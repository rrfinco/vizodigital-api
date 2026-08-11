<?php

namespace App\Services\Recharge;

use App\Enums\RechargeProvider;
use App\Models\User;
use App\Models\Whitelabel;

class RechargeProviderResolver
{
    public function forUser(User $user): RechargeProvider
    {
        if ($user->whitelabel_id) {
            $wl = $user->relationLoaded('whitelabel')
                ? $user->whitelabel
                : Whitelabel::query()->find($user->whitelabel_id);

            if ($wl?->recharge_provider instanceof RechargeProvider) {
                return $wl->recharge_provider;
            }

            if (is_string($wl?->recharge_provider) && $wl->recharge_provider !== '') {
                return RechargeProvider::tryFrom($wl->recharge_provider) ?? RechargeProvider::Roundpay;
            }

            return RechargeProvider::Roundpay;
        }

        if ($user->recharge_provider instanceof RechargeProvider) {
            return $user->recharge_provider;
        }

        if (is_string($user->recharge_provider) && $user->recharge_provider !== '') {
            return RechargeProvider::tryFrom($user->recharge_provider) ?? RechargeProvider::Roundpay;
        }

        return RechargeProvider::Roundpay;
    }
}
