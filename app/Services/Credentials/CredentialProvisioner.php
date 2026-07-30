<?php

namespace App\Services\Credentials;

use App\Enums\CredentialStatus;
use App\Enums\EnvironmentSlug;
use App\Models\ApiCredential;
use App\Models\ApiEnvironment;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CredentialProvisioner
{
    public function provisionUat(User $user): ApiCredential
    {
        $environment = $this->environment(EnvironmentSlug::Uat);

        return ApiCredential::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'api_environment_id' => $environment->id,
            ],
            [
                'client_id' => $this->clientId('uat', $user),
                'api_secret' => 'sec_uat_' . Str::random(40),
                'merchant_id' => 'MERCHANT_UAT_'.$user->id,
                'webhook_secret' => 'whsec_'.Str::lower(Str::random(32)),
                'status' => CredentialStatus::Active,
                'notes' => 'Auto-provisioned after KYC approval.',
            ]
        );
    }

    public function unlockProduction(User $user, ?string $notes = null): ApiCredential
    {
        $environment = $this->environment(EnvironmentSlug::Production);

        $existing = ApiCredential::query()
            ->where('user_id', $user->id)
            ->where('api_environment_id', $environment->id)
            ->first();

        if ($existing?->status === CredentialStatus::Active && filled($existing->api_secret)) {
            return $existing;
        }

        return ApiCredential::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'api_environment_id' => $environment->id,
            ],
            [
                'client_id' => $existing?->client_id ?: $this->clientId('live', $user),
                'api_secret' => 'sec_live_' . Str::random(40),
                'merchant_id' => $existing?->merchant_id ?: 'MERCHANT_LIVE_'.$user->id,
                'webhook_secret' => 'whsec_'.Str::lower(Str::random(32)),
                'status' => CredentialStatus::Active,
                'notes' => $notes ?: 'Production access unlocked by admin.',
            ]
        );
    }

    /**
     * @throws ValidationException
     */
    public function assertUsable(User $user, EnvironmentSlug $slug): ApiCredential
    {
        $environment = ApiEnvironment::query()
            ->where('slug', $slug->value)
            ->where('is_enabled', true)
            ->first();

        if (! $environment) {
            throw ValidationException::withMessages([
                'environment' => ucfirst($slug->value).' environment is not configured.',
            ]);
        }

        $credential = ApiCredential::query()
            ->where('user_id', $user->id)
            ->where('api_environment_id', $environment->id)
            ->first();

        if (! $credential) {
            throw ValidationException::withMessages([
                'credentials' => ucfirst($slug->value).' credentials are not issued yet.',
            ]);
        }

        if (! $credential->status->isUsable()) {
            throw ValidationException::withMessages([
                'credentials' => ucfirst($slug->value).' credentials are '.$credential->status->label().'. Contact an admin.',
            ]);
        }

        return $credential->loadMissing('environment');
    }

    /**
     * @throws ValidationException
     */
    public function authenticateClient(string $clientId, string $apiSecret, EnvironmentSlug $slug): ApiCredential
    {
        $environment = ApiEnvironment::query()
            ->where('slug', $slug->value)
            ->where('is_enabled', true)
            ->first();

        if (! $environment) {
            throw ValidationException::withMessages([
                'environment' => ucfirst($slug->value).' environment is not configured.',
            ]);
        }

        $credential = ApiCredential::query()
            ->where('client_id', $clientId)
            ->where('api_environment_id', $environment->id)
            ->first();

        if (! $credential || $credential->api_secret !== $apiSecret) {
            throw ValidationException::withMessages([
                'client_id' => 'Invalid '.strtoupper($slug->value).' client credentials.',
            ]);
        }

        if (! $credential->status->isUsable()) {
            throw ValidationException::withMessages([
                'client_id' => strtoupper($slug->value).' credentials are not active ('.$credential->status->label().').',
            ]);
        }

        $user = $credential->user;

        if (! $user?->isOnboardingApproved()) {
            throw ValidationException::withMessages([
                'client_id' => 'Account is not approved for API access.',
            ]);
        }

        return $credential->loadMissing('environment', 'user');
    }

    private function environment(EnvironmentSlug $slug): ApiEnvironment
    {
        $environment = ApiEnvironment::query()
            ->where('slug', $slug->value)
            ->where('is_enabled', true)
            ->first();

        if (! $environment) {
            throw ValidationException::withMessages([
                'environment' => ucfirst($slug->value).' environment is not configured. Set the base URL in Admin → Environments.',
            ]);
        }

        if (! filled($environment->base_url)) {
            throw ValidationException::withMessages([
                'environment' => ucfirst($slug->value).' base URL is missing. Set it in Admin → Environments.',
            ]);
        }

        return $environment;
    }

    private function clientId(string $prefix, User $user): string
    {
        return sprintf('%s_client_%d_%s', $prefix, $user->id, Str::lower(Str::random(8)));
    }
}
