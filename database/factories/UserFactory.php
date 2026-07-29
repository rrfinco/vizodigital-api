<?php

namespace Database\Factories;

use App\Enums\OnboardingStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'onboarding_status' => OnboardingStatus::Approved,
            'approved_at' => now(),
        ];
    }

    public function pendingKyc(): static
    {
        return $this->state(fn (array $attributes) => [
            'onboarding_status' => OnboardingStatus::PendingKyc,
            'approved_at' => null,
            'kyc_token' => Str::random(64),
            'kyc_token_expires_at' => now()->addDays(7),
        ]);
    }

    public function kycSubmitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'onboarding_status' => OnboardingStatus::KycSubmitted,
            'kyc_submitted_at' => now(),
            'approved_at' => null,
            'kyc_token' => null,
            'kyc_token_expires_at' => null,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
