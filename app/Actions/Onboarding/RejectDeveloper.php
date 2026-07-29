<?php

namespace App\Actions\Onboarding;

use App\Enums\OnboardingStatus;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RejectDeveloper
{
    public function handle(User $user, string $reason): User
    {
        if ($user->onboarding_status === OnboardingStatus::Approved) {
            throw ValidationException::withMessages([
                'onboarding_status' => 'Approved developers cannot be rejected from KYC review. Revoke credentials separately.',
            ]);
        }

        $user->forceFill([
            'onboarding_status' => OnboardingStatus::Rejected,
            'rejection_reason' => $reason,
            'approved_at' => null,
            'approved_by' => null,
            'kyc_token' => Str::random(64),
            'kyc_token_expires_at' => now()->addDays(7),
        ])->save();

        return $user->refresh();
    }
}
