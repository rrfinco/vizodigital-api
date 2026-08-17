<?php

namespace Tests\Feature\Api\V1;

use App\Enums\OnboardingStatus;
use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserPlanApiAccess;
use App\Models\WalletTransaction;
use App\Models\Whitelabel;
use App\Models\WhitelabelPlanApiAccess;
use App\Models\WhitelabelWalletTransaction;
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function profilePayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Rajesh',
            'last_name' => 'Jha',
            'mobile_no' => '9110409809',
            'email' => 'rajesh@example.com',
            'dob' => '1990-12-10',
            'company' => 75,
            'occupation' => 1,
            'monthly_salary' => 50000,
            'itr_amount' => 0,
            'gender' => 'Male',
            'pincode' => 560001,
            'address' => 'MG Road, Bengaluru',
            'category' => 'Individual',
            'category_id' => 3,
            'pan' => 'ABCDE1234F',
        ], $overrides);
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
            ->assertJsonPath('fee', 0)
            ->assertJsonPath('wallet_balance', 100);

        $this->assertSame(100.0, (float) $this->user->fresh()->wallet_balance);
        $this->assertSame(0, WalletTransaction::query()->count());

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

    public function test_create_lead_uses_profile_customer_id_when_provided(): void
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
            'customer_id' => 'aUczK1BLZm1lRmtSNEZ6SGJTaHl0QT09',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://tryleadapi.example.test/api/b2b/lead'
                && $request['customer_id'] === 'aUczK1BLZm1lRmtSNEZ6SGJTaHl0QT09';
        });
    }

    public function test_create_lead_profile_requires_authentication(): void
    {
        $this->postJson(route('api.v1.leads.profile'), $this->profilePayload())
            ->assertUnauthorized();
    }

    public function test_create_lead_profile_disabled_returns_403(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $this->postJson(route('api.v1.leads.profile'), $this->profilePayload())
            ->assertStatus(403)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'This API is not enabled for your account. Contact admin.');
    }

    public function test_create_lead_profile_validation(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION);
        $this->actingAs($this->user, 'sanctum');

        $this->postJson(route('api.v1.leads.profile'), [])
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');

        $this->postJson(route('api.v1.leads.profile'), $this->profilePayload([
            'pan' => 'INVALID',
            'mobile_no' => '123',
        ]))
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_create_lead_profile_success_sends_form_and_query(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION, 0.10);
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'tryleadapi.example.test/*' => Http::response([
                'status' => true,
                'data' => [
                    'mobile_no' => '9110409809',
                    'profile_details' => [
                        'customer_id' => 'aUczK1BLZm1lRmtSNEZ6SGJTaHl0QT09',
                        'category_id' => '3',
                        'product_id' => null,
                    ],
                ],
                'message' => 'Customer profile has been created.',
            ], 200),
        ]);

        $this->postJson(route('api.v1.leads.profile'), $this->profilePayload([
            'itr_amount' => 99999,
        ]))
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Customer profile has been created.')
            ->assertJsonPath('data.customer_id', 'aUczK1BLZm1lRmtSNEZ6SGJTaHl0QT09')
            ->assertJsonPath('data.mobile_no', '9110409809')
            ->assertJsonPath('data.category_id', '3')
            ->assertJsonPath('data.product_id', null)
            ->assertJsonPath('fee', 0)
            ->assertJsonPath('wallet_balance', 100);

        Http::assertSent(function ($request): bool {
            $query = [];
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return str_starts_with($request->url(), 'https://tryleadapi.example.test/api/b2b/createLeadProfile')
                && ($query['mobile_no'] ?? null) === '9110409809'
                && (string) ($query['category_id'] ?? '') === '3'
                && $request['first_name'] === 'Rajesh'
                && $request['last_name'] === 'Jha'
                && $request['email'] === 'rajesh@example.com'
                && $request['pan'] === 'ABCDE1234F'
                && $request['pan_no'] === 'ABCDE1234F'
                && (int) $request['monthly_salary'] === 50000
                && (int) $request['itr_amount'] === 0
                && ! isset($request['customer_id'])
                && $request->hasHeader('x-api-key', 'TEST_KEY')
                && $request->isForm();
        });
    }

    public function test_create_lead_profile_update_sends_customer_id(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION, 0.10);
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'tryleadapi.example.test/*' => Http::response([
                'status' => true,
                'data' => [
                    'mobile_no' => '9110409809',
                    'profile_details' => [
                        'customer_id' => 'aUczK1BLZm1lRmtSNEZ6SGJTaHl0QT09',
                        'category_id' => '3',
                        'product_id' => null,
                    ],
                ],
                'message' => 'Customer profile has been updated.',
            ], 200),
        ]);

        $this->postJson(route('api.v1.leads.profile'), $this->profilePayload([
            'customer_id' => 'aUczK1BLZm1lRmtSNEZ6SGJTaHl0QT09',
        ]))
            ->assertOk()
            ->assertJsonPath('message', 'Customer profile has been updated.')
            ->assertJsonPath('data.customer_id', 'aUczK1BLZm1lRmtSNEZ6SGJTaHl0QT09');

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/api/b2b/createLeadProfile')
                && $request['customer_id'] === 'aUczK1BLZm1lRmtSNEZ6SGJTaHl0QT09';
        });
    }

    public function test_create_lead_profile_self_employed_sends_itr_and_zero_salary(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION, 0.10);
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'tryleadapi.example.test/*' => Http::response([
                'status' => true,
                'data' => [
                    'mobile_no' => '9110409809',
                    'profile_details' => [
                        'customer_id' => 'aUczK1BLZm1lRmtSNEZ6SGJTaHl0QT09',
                        'category_id' => '3',
                        'product_id' => null,
                    ],
                ],
                'message' => 'Customer profile has been created.',
            ], 200),
        ]);

        $this->postJson(route('api.v1.leads.profile'), $this->profilePayload([
            'occupation' => 2,
            'monthly_salary' => 50000,
            'itr_amount' => 120000,
        ]))->assertOk();

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/api/b2b/createLeadProfile')
                && (int) $request['occupation'] === 2
                && (int) $request['monthly_salary'] === 0
                && (int) $request['itr_amount'] === 120000;
        });
    }

    public function test_create_lead_profile_forwards_provider_error(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION, 0.10);
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'tryleadapi.example.test/*' => Http::response([
                'status' => false,
                'data' => [],
                'message' => 'Pan no ABCDE1234F is associated with another mobile number XXXXXXXX01',
            ], 200),
        ]);

        $this->postJson(route('api.v1.leads.profile'), $this->profilePayload())
            ->assertStatus(400)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Pan no ABCDE1234F is associated with another mobile number XXXXXXXX01');
    }

    public function test_uat_token_returns_mock_lead_profile_without_http(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION, 0.10);

        $plain = $this->user->createToken('uat-lead-profile-test', ['*', 'environment:uat'])->plainTextToken;

        Http::fake();

        $this->withToken($plain)->postJson(route('api.v1.leads.profile'), $this->profilePayload())
            ->assertOk()
            ->assertJsonPath('data.customer_id', 'UAT-CUST-9809')
            ->assertJsonPath('data.mobile_no', '9110409809')
            ->assertJsonPath('fee', 0);

        Http::assertNothingSent();
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

    public function test_lead_status_pending_does_not_charge(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION, 0.10);
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'tryleadapi.example.test/*' => Http::response([
                'status' => true,
                'data' => [
                    'lead_code' => 'BS-LEAD-987654',
                    'lead_status' => 'pending',
                ],
            ], 200),
        ]);

        $this->getJson(route('api.v1.leads.status', ['lead_code' => 'BS-LEAD-987654']))
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.lead_code', 'BS-LEAD-987654')
            ->assertJsonPath('data.lead_status', 'pending')
            ->assertJsonPath('fee', 0)
            ->assertJsonPath('wallet_balance', 100);

        $this->assertSame(100.0, (float) $this->user->fresh()->wallet_balance);
        $this->assertSame(0, WalletTransaction::query()->count());
        $this->assertDatabaseHas('lead_status_snapshots', [
            'user_id' => $this->user->id,
            'lead_code' => 'BS-LEAD-987654',
            'last_status' => 'pending',
        ]);
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
                    'lead_status' => 'submitted',
                ],
            ], 200),
        ]);

        $this->getJson(route('api.v1.leads.status', ['lead_code' => 'BS-LEAD-987654']))
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.lead_code', 'BS-LEAD-987654')
            ->assertJsonPath('data.lead_status', 'submitted')
            ->assertJsonPath('fee', 0);

        $this->assertSame(0, WalletTransaction::query()->count());

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
        $this->assertSame(0, WalletTransaction::query()->count());
    }

    public function test_lead_status_charges_once_when_status_first_becomes_approved(): void
    {
        $this->enableService(ProductApiService::SERVICE_LEAD_GENERATION, 0.10);
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'tryleadapi.example.test/*' => Http::sequence()
                ->push([
                    'status' => true,
                    'data' => [
                        'lead_code' => 'BS-LEAD-987654',
                        'lead_status' => 'pending',
                    ],
                ], 200)
                ->push([
                    'status' => true,
                    'data' => [
                        'lead_code' => 'BS-LEAD-987654',
                        'lead_status' => 'approved',
                    ],
                ], 200)
                ->push([
                    'status' => true,
                    'data' => [
                        'lead_code' => 'BS-LEAD-987654',
                        'lead_status' => 'approved',
                    ],
                ], 200),
        ]);

        $this->getJson(route('api.v1.leads.status', ['lead_code' => 'BS-LEAD-987654']))
            ->assertOk()
            ->assertJsonPath('data.lead_status', 'pending')
            ->assertJsonPath('fee', 0);

        $this->getJson(route('api.v1.leads.status', ['lead_code' => 'BS-LEAD-987654']))
            ->assertOk()
            ->assertJsonPath('data.lead_status', 'approved')
            ->assertJsonPath('fee', 0.10)
            ->assertJsonPath('wallet_balance', 99.90);

        $this->getJson(route('api.v1.leads.status', ['lead_code' => 'BS-LEAD-987654']))
            ->assertOk()
            ->assertJsonPath('data.lead_status', 'approved')
            ->assertJsonPath('fee', 0)
            ->assertJsonPath('wallet_balance', 99.90);

        $this->assertSame(99.90, (float) $this->user->fresh()->wallet_balance);
        $this->assertSame(1, WalletTransaction::query()->count());
        $this->assertDatabaseHas('lead_status_snapshots', [
            'user_id' => $this->user->id,
            'lead_code' => 'BS-LEAD-987654',
            'last_status' => 'approved',
        ]);
    }

    public function test_lead_status_insufficient_wallet_does_not_store_approved(): void
    {
        $this->user->update(['wallet_balance' => 0.05]);
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
            ->assertStatus(400)
            ->assertJsonPath('status', 'error');

        $this->assertSame(0.05, (float) $this->user->fresh()->wallet_balance);
        $this->assertSame(0, WalletTransaction::query()->count());
        $this->assertDatabaseMissing('lead_status_snapshots', [
            'user_id' => $this->user->id,
            'lead_code' => 'BS-LEAD-987654',
        ]);

        $this->user->update(['wallet_balance' => 100]);

        $this->getJson(route('api.v1.leads.status', ['lead_code' => 'BS-LEAD-987654']))
            ->assertOk()
            ->assertJsonPath('data.lead_status', 'approved')
            ->assertJsonPath('fee', 0.10);

        $this->assertSame(99.90, (float) $this->user->fresh()->wallet_balance);
    }

    public function test_whitelabel_create_lead_does_not_charge(): void
    {
        $whitelabel = Whitelabel::factory()->withFloat(100)->create();
        $developer = User::factory()->forWhitelabel($whitelabel->id)->create([
            'wallet_balance' => 50,
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $developer->assignRole(Role::Developer->value);

        WhitelabelPlanApiAccess::query()->create([
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
            ->assertJsonPath('fee', 0)
            ->assertJsonPath('wallet_balance', 50);

        $this->assertSame(50.0, (float) $developer->fresh()->wallet_balance);
        $this->assertSame(100.0, (float) $whitelabel->fresh()->wallet_balance);
        $this->assertSame(0, WhitelabelWalletTransaction::query()->count());
    }

    public function test_whitelabel_lead_status_debits_fee_and_credits_margin_on_new_approved(): void
    {
        $whitelabel = Whitelabel::factory()->withFloat(100)->create();
        $developer = User::factory()->forWhitelabel($whitelabel->id)->create([
            'wallet_balance' => 50,
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $developer->assignRole(Role::Developer->value);

        WhitelabelPlanApiAccess::query()->create([
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
            'tryleadapi.example.test/*' => Http::sequence()
                ->push([
                    'status' => true,
                    'data' => [
                        'lead_code' => 'BS-LEAD-987654',
                        'lead_status' => 'pending',
                    ],
                ], 200)
                ->push([
                    'status' => true,
                    'data' => [
                        'lead_code' => 'BS-LEAD-987654',
                        'lead_status' => 'approved',
                    ],
                ], 200)
                ->push([
                    'status' => true,
                    'data' => [
                        'lead_code' => 'BS-LEAD-987654',
                        'lead_status' => 'approved',
                    ],
                ], 200),
        ]);

        $this->getJson(route('api.v1.leads.status', ['lead_code' => 'BS-LEAD-987654']))
            ->assertOk()
            ->assertJsonPath('fee', 0);

        $this->assertSame(50.0, (float) $developer->fresh()->wallet_balance);
        $this->assertSame(100.0, (float) $whitelabel->fresh()->wallet_balance);

        $this->getJson(route('api.v1.leads.status', ['lead_code' => 'BS-LEAD-987654']))
            ->assertOk()
            ->assertJsonPath('fee', 0.15)
            ->assertJsonPath('wallet_balance', 49.85);

        $this->assertSame(49.85, (float) $developer->fresh()->wallet_balance);
        $this->assertSame(99.90, (float) $whitelabel->fresh()->wallet_balance);

        $this->assertDatabaseHas('whitelabel_wallet_transactions', [
            'whitelabel_id' => $whitelabel->id,
            'type' => 'debit',
            'amount' => -0.15,
        ]);
        $this->assertDatabaseHas('whitelabel_wallet_transactions', [
            'whitelabel_id' => $whitelabel->id,
            'type' => 'credit',
            'amount' => 0.05,
        ]);

        $this->getJson(route('api.v1.leads.status', ['lead_code' => 'BS-LEAD-987654']))
            ->assertOk()
            ->assertJsonPath('fee', 0)
            ->assertJsonPath('wallet_balance', 49.85);

        $this->assertSame(49.85, (float) $developer->fresh()->wallet_balance);
        $this->assertSame(99.90, (float) $whitelabel->fresh()->wallet_balance);
    }

    public function test_whitelabel_inactive_wl_service_blocks_lead_status(): void
    {
        $whitelabel = Whitelabel::factory()->withFloat(100)->create();
        $developer = User::factory()->forWhitelabel($whitelabel->id)->create([
            'wallet_balance' => 50,
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $developer->assignRole(Role::Developer->value);

        UserPlanApiAccess::query()->create([
            'user_id' => $developer->id,
            'service' => ProductApiService::SERVICE_LEAD_GENERATION,
            'per_call_fee' => 0.15,
            'status' => true,
        ]);

        $this->actingAs($developer, 'sanctum');

        $this->getJson(route('api.v1.leads.status', ['lead_code' => 'BS-LEAD-987654']))
            ->assertStatus(503)
            ->assertJsonPath('code', 'SERVICE_UNAVAILABLE');

        $this->assertSame(50.0, (float) $developer->fresh()->wallet_balance);
        $this->assertSame(100.0, (float) $whitelabel->fresh()->wallet_balance);
    }
}
