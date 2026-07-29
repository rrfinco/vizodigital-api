<?php

namespace Database\Seeders;

use App\Enums\CredentialStatus;
use App\Enums\EnvironmentSlug;
use App\Models\ApiCredential;
use App\Models\ApiEnvironment;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApiCredentialSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'user@portal.test')->first();

        if (! $user) {
            return;
        }

        $uat = ApiEnvironment::query()->where('slug', EnvironmentSlug::Uat)->first();
        $production = ApiEnvironment::query()->where('slug', EnvironmentSlug::Production)->first();

        if ($uat) {
            ApiCredential::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'api_environment_id' => $uat->id,
                ],
                [
                    'client_id' => 'uat_client_portal_user',
                    'api_secret' => 'uat_secret_demo_change_me',
                    'merchant_id' => 'MERCHANT_UAT_001',
                    'webhook_secret' => 'uat_whsec_demo_change_me',
                    'status' => CredentialStatus::Active,
                    'notes' => 'Sandbox credentials for local demo.',
                ]
            );
        }

        if ($production) {
            ApiCredential::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'api_environment_id' => $production->id,
                ],
                [
                    'client_id' => 'prod_client_portal_user',
                    'api_secret' => 'prod_secret_locked_placeholder',
                    'merchant_id' => 'MERCHANT_PROD_001',
                    'webhook_secret' => null,
                    'status' => CredentialStatus::Pending,
                    'notes' => 'Production access pending admin approval after UAT sign-off.',
                ]
            );
        }
    }
}
