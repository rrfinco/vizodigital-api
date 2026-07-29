<?php

namespace App\Actions\Onboarding;

use App\Enums\OnboardingStatus;
use App\Models\User;
use App\Services\Credentials\CredentialProvisioner;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveDeveloper
{
    public function __construct(
        private readonly CredentialProvisioner $credentials,
    ) {}

    public function handle(User $user, User $approver): User
    {
        if ($user->onboarding_status === OnboardingStatus::Approved) {
            return $user;
        }

        if ($user->onboarding_status !== OnboardingStatus::KycSubmitted) {
            throw ValidationException::withMessages([
                'onboarding_status' => 'Only KYC-submitted applications can be approved.',
            ]);
        }

        return DB::transaction(function () use ($user, $approver): User {
            $user->forceFill([
                'onboarding_status' => OnboardingStatus::Approved,
                'approved_at' => now(),
                'approved_by' => $approver->id,
                'rejection_reason' => null,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            $this->credentials->provisionUat($user);

            return $user->refresh();
        });
    }
}
