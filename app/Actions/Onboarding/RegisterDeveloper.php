<?php

namespace App\Actions\Onboarding;

use App\Enums\OnboardingStatus;
use App\Enums\Role as RoleEnum;
use App\Mail\KycInviteMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegisterDeveloper
{
    /**
     * @param  array{name: string, email: string, password: string, company_name?: string|null, phone?: string|null}  $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'company_name' => $data['company_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'onboarding_status' => OnboardingStatus::PendingKyc,
                'kyc_token' => Str::random(64),
                'kyc_token_expires_at' => now()->addDays(7),
            ]);

            $user->assignRole(RoleEnum::Developer->value);

            Mail::to($user->email)->send(new KycInviteMail($user));

            return $user;
        });
    }
}
