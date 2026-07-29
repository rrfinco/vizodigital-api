<?php

namespace Tests\Feature\Onboarding;

use App\Actions\Onboarding\ApproveDeveloper;
use App\Enums\CredentialStatus;
use App\Enums\EnvironmentSlug;
use App\Enums\OnboardingStatus;
use App\Enums\Role;
use App\Models\ApiCredential;
use App\Models\ApiEnvironment;
use App\Models\User;
use Database\Seeders\CmsFoundationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KycOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CmsFoundationSeeder::class);
        Storage::fake('local');
        Mail::fake();
    }

    public function test_developer_can_register_and_receives_kyc_email(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'New Merchant',
            'email' => 'merchant@example.com',
            'company_name' => 'Merchant Co',
            'phone' => '+910000000000',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('register.thanks'));

        $user = User::query()->where('email', 'merchant@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole(Role::Developer->value));
        $this->assertSame(OnboardingStatus::PendingKyc, $user->onboarding_status);
        $this->assertNotNull($user->kyc_token);
        Mail::assertSent(\App\Mail\KycInviteMail::class);
    }

    public function test_unapproved_developer_cannot_login(): void
    {
        $user = User::factory()->pendingKyc()->create([
            'email' => 'pending@example.com',
            'password' => 'password',
        ]);
        $user->assignRole(Role::Developer->value);

        $this->post(route('login.store'), [
            'email' => 'pending@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_kyc_submit_approve_issues_uat_credentials(): void
    {
        $user = User::factory()->pendingKyc()->create([
            'email' => 'kyc@example.com',
            'password' => 'password',
        ]);
        $user->assignRole(Role::Developer->value);

        $this->post(route('onboarding.kyc.store', ['token' => $user->kyc_token]), [
            'company_name' => 'KYC Co',
            'documents' => [
                [
                    'type' => 'company_registration',
                    'file' => UploadedFile::fake()->create('reg.pdf', 100, 'application/pdf'),
                ],
                [
                    'type' => 'identity_proof',
                    'file' => UploadedFile::fake()->create('id.pdf', 80, 'application/pdf'),
                ],
            ],
        ])->assertRedirect(route('onboarding.kyc.submitted'));

        $user->refresh();
        $this->assertSame(OnboardingStatus::KycSubmitted, $user->onboarding_status);
        $this->assertCount(2, $user->kycDocuments);

        $admin = User::factory()->create(['email' => 'approver@example.com']);
        $admin->assignRole(Role::Admin->value);

        app(ApproveDeveloper::class)->handle($user, $admin);

        $user->refresh();
        $this->assertSame(OnboardingStatus::Approved, $user->onboarding_status);

        $uat = ApiEnvironment::query()->where('slug', EnvironmentSlug::Uat)->first();
        $credential = ApiCredential::query()
            ->where('user_id', $user->id)
            ->where('api_environment_id', $uat->id)
            ->first();

        $this->assertNotNull($credential);
        $this->assertSame(CredentialStatus::Active, $credential->status);

        $this->post(route('login.store'), [
            'email' => 'kyc@example.com',
            'password' => 'password',
        ])->assertRedirect(route('docs.overview'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_client_credentials_require_active_env_keys(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Developer->value);

        $uat = ApiEnvironment::query()->where('slug', EnvironmentSlug::Uat)->first();

        $this->postJson('/api/v1/auth/client-credentials', [
            'client_id' => 'missing',
            'api_secret' => 'nope',
            'environment' => 'uat',
        ])->assertUnauthorized();

        $credential = ApiCredential::query()->create([
            'user_id' => $user->id,
            'api_environment_id' => $uat->id,
            'client_id' => 'uat_client_test',
            'api_secret' => 'secret-value',
            'status' => CredentialStatus::Pending,
        ]);

        $this->postJson('/api/v1/auth/client-credentials', [
            'client_id' => 'uat_client_test',
            'api_secret' => 'secret-value',
            'environment' => 'uat',
        ])->assertUnauthorized();

        $credential->update(['status' => CredentialStatus::Active]);

        $this->postJson('/api/v1/auth/client-credentials', [
            'client_id' => 'uat_client_test',
            'api_secret' => 'secret-value',
            'environment' => 'uat',
        ])->assertOk()
            ->assertJsonPath('environment', 'uat')
            ->assertJsonStructure(['token', 'base_url']);
    }
}
