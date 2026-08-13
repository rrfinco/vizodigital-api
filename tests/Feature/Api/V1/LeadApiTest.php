<?php

namespace Tests\Feature\Api\V1;

use App\Enums\OnboardingStatus;
use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserPlanApiAccess;
use App\Models\WalletTransaction;
use App\Services\ProductApi\ProductApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LeadApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::create(['name' => Role::Developer->value]);

        $this->user = User::factory()->create([
            'wallet_balance' => 100.0000,
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $this->user->assignRole(Role::Developer->value);

        Setting::setValue('banksathi_base_url', 'https://tryleadapi.example.test', 'banksathi');
        Setting::setValue('banksathi_iv', 'TEST_IV', 'banksathi');
        Setting::setValue('banksathi_api_key', 'TEST_KEY', 'banksathi');
        Setting::setValue('banksathi_customer_id', 'TEST_CUSTOMER_ID', 'banksathi');
    }

    private function enableService(string $service, float $fee = 0.10, bool $active = true): void
    {
        UserPlanApiAccess::query()->updateOrCreate(
            [
                'user_id' => $this->user->id,
                'service' => $service,
            ],
            [
                'per_call_fee' => $fee,
                'status' => $active,
            ],
        );
    }

    public function test_create_lead_requires_authentication(): void
    {
        $this->postJson(route('api.v1.leads.store'), [
            'product_id' => '12345',
        ])->assertUnauthorized();
    }

    public function test_create_lead_disabled_returns_403(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $this->postJson(route('api.v1.leads.store'), [
            'product_id' => '12345',
        ])
            ->assertStatus(403)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'This API is not enabled for your account. Contact admin.');
    }

    public function test_create_lead_validation(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION);
        $this->actingAs($this->user, 'sanctum');

        $this->postJson(route('api.v1.leads.store'), [])
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_create_lead_success_sends_customer_id_and_category_typo(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION, 0.10);
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'tryleadapi.example.test/*' => Http::response([
                'status' => true,
                'data' => [
                    'lead_code' => 'BS-LEAD-987654',
                    'campaign_url' => 'https://apply.example.test/campaign/xyz789',
                ],
                'message' => 'Lead created',
            ], 200),
        ]);

        $this->postJson(route('api.v1.leads.store'), [
            'product_id' => '12345',
            'category_id' => 3,
            'required_amount' => 50000,
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.lead_code', 'BS-LEAD-987654')
            ->assertJsonPath('data.campaign_url', 'https://apply.example.test/campaign/xyz789')
            ->assertJsonPath('fee', 0.10)
            ->assertJsonPath('wallet_balance', 99.90);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://tryleadapi.example.test/api/b2b/lead'
                && $request['customer_id'] === 'TEST_CUSTOMER_ID'
                && $request['product_id'] === '12345'
                && $request['category_id'] == 3
                && $request['categroy_id'] == 3
                && $request['required_amount'] == 50000
                && $request->hasHeader('x-api-key', 'TEST_KEY');
        });
    }

    public function test_uat_token_returns_mock_lead_without_http_or_fee(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION, 0.10);

        $plain = $this->user->createToken('uat-lead-test', ['*', 'environment:uat'])->plainTextToken;

        Http::fake();

        $this->withToken($plain)->postJson(route('api.v1.leads.store'), [
            'product_id' => '12345',
        ])
            ->assertOk()
            ->assertJsonPath('data.lead_code', 'UAT-LEAD-12345')
            ->assertJsonPath('fee', 0);

        Http::assertNothingSent();
        $this->assertSame(0, WalletTransaction::query()->count());
    }

    public function test_lead_status_requires_authentication(): void
    {
        $this->getJson(route('api.v1.leads.status', ['lead_code' => 'BS-LEAD-987654']))
            ->assertUnauthorized();
    }

    public function test_lead_status_validation(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION);
        $this->actingAs($this->user, 'sanctum');

        $this->getJson(route('api.v1.leads.status'))
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_lead_status_success(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION, 0.10);
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'tryleadapi.example.test/*' => Http::response([
                'status' => true,
                'data' => [
                    'lead_code' => 'BS-LEAD-987654',
                    'lead_status' => 'approved',
                ],
            ], 200),
        ]);

        $this->getJson(route('api.v1.leads.status', ['lead_code' => 'BS-LEAD-987654']))
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.lead_code', 'BS-LEAD-987654')
            ->assertJsonPath('data.lead_status', 'approved')
            ->assertJsonPath('fee', 0);

        Http::assertSent(function ($request): bool {
            return str_starts_with($request->url(), 'https://tryleadapi.example.test/api/b2b/leadStatus')
                && $request['lead_code'] === 'BS-LEAD-987654';
        });
    }

    public function test_uat_token_returns_mock_lead_status_without_http_or_fee(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION, 0.10);

        $plain = $this->user->createToken('uat-lead-status-test', ['*', 'environment:uat'])->plainTextToken;

        Http::fake();

        $this->withToken($plain)->getJson(route('api.v1.leads.status', ['lead_code' => 'BS-LEAD-987654']))
            ->assertOk()
            ->assertJsonPath('data.lead_code', 'BS-LEAD-987654')
            ->assertJsonPath('data.lead_status', 'pending')
            ->assertJsonPath('fee', 0);

        Http::assertNothingSent();
    }

    public function test_whitelabel_create_lead_debits_per_lead_fee_and_credits_margin(): void
    {
        $whitelabel = \App\Models\Whitelabel::factory()->withFloat(100)->create();
        $developer = User::factory()->forWhitelabel($whitelabel->id)->create([
            'wallet_balance' => 50,
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $developer->assignRole(Role::Developer->value);

        \App\Models\WhitelabelPlanApiAccess::query()->create([
            'whitelabel_id' => $whitelabel->id,
            'service' => ProductApiService::SERVICE_LEAD_GENERATION,
            'per_call_fee' => 0.10,
            'status' => true,
        ]);

        UserPlanApiAccess::query()->create([
            'user_id' => $developer->id,
            'service' => ProductApiService::SERVICE_LEAD_GENERATION,
            'per_call_fee' => 0.15,
            'status' => true,
        ]);

        $this->actingAs($developer, 'sanctum');

        Http::fake([
            'tryleadapi.example.test/*' => Http::response([
                'status' => true,
                'data' => [
                    'lead_code' => 'BS-LEAD-987654',
                    'campaign_url' => 'https://apply.example.test/campaign/xyz789',
                ],
            ], 200),
        ]);

        $this->postJson(route('api.v1.leads.store'), [
            'product_id' => '12345',
        ])
            ->assertOk()
            ->assertJsonPath('fee', 0.15)
            ->assertJsonPath('wallet_balance', 49.85);

        $this->assertSame(49.85, (float) $developer->fresh()->wallet_balance);
        $this->assertSame(99.90, (float) $whitelabel->fresh()->wallet_balance);
    }
}
