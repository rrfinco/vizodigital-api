<?php

namespace Tests\Feature\Api\V1;

use App\Enums\OnboardingStatus;
use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserPlanApiAccess;
use App\Models\WalletTransaction;
use App\Services\PlanApi\PlanApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlanApiTest extends TestCase
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

        Setting::setValue('ekychub_username', 'TEST_USER', 'payment');
        Setting::setValue('ekychub_token', 'TEST_TOKEN', 'payment');
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

    public function test_operator_fetch_requires_authentication(): void
    {
        $this->postJson(route('api.v1.plan.operator-fetch'), [
            'mobile' => '9468455123',
            'orderid' => 'OPF1',
        ])->assertUnauthorized();
    }

    public function test_operator_fetch_disabled_returns_403(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $this->postJson(route('api.v1.plan.operator-fetch'), [
            'mobile' => '9468455123',
            'orderid' => 'OPF_OFF',
        ])
            ->assertStatus(403)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'This API is not enabled for your account. Contact admin.');
    }

    public function test_operator_fetch_validation(): void
    {
        $this->enableService(PlanApiService::SERVICE_OPERATOR_FETCH);
        $this->actingAs($this->user, 'sanctum');

        $this->postJson(route('api.v1.plan.operator-fetch'), [
            'mobile' => '123',
            'orderid' => '',
        ])
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_operator_fetch_success_debits_wallet(): void
    {
        $this->enableService(PlanApiService::SERVICE_OPERATOR_FETCH, 0.10);
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'connect.ekychub.in/*' => Http::response([
                'status' => 'Success',
                'number' => '9468455xxx',
                'company' => 'BSNL',
                'circle' => 'Haryana',
                'circle_code' => '96',
                'message' => 'Operator fetched Successfully',
            ], 200),
        ]);

        $response = $this->postJson(route('api.v1.plan.operator-fetch'), [
            'mobile' => '9468455123',
            'orderid' => 'OPF_OK',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.company', 'BSNL')
            ->assertJsonPath('data.circle_code', '96')
            ->assertJsonPath('fee', 0.10)
            ->assertJsonPath('wallet_balance', 99.90);

        $this->assertEquals(99.90, (float) $this->user->fresh()->wallet_balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->user->id,
            'type' => 'debit',
            'amount' => -0.10,
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'operator_fetch')
                && $request['username'] === 'TEST_USER'
                && $request['token'] === 'TEST_TOKEN'
                && $request['mobile'] === '9468455123'
                && $request['orderid'] === 'OPF_OK';
        });
    }

    public function test_operator_fetch_insufficient_balance(): void
    {
        $this->user->update(['wallet_balance' => 0.05]);
        $this->enableService(PlanApiService::SERVICE_OPERATOR_FETCH, 0.10);
        $this->actingAs($this->user, 'sanctum');

        $this->postJson(route('api.v1.plan.operator-fetch'), [
            'mobile' => '9468455123',
            'orderid' => 'OPF_LOW',
        ])
            ->assertStatus(400)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Insufficient wallet balance. Please recharge your wallet. Required: ₹0.1, Available: ₹0.05');

        $this->assertEquals(0.05, (float) $this->user->fresh()->wallet_balance);
        $this->assertSame(0, WalletTransaction::query()->count());
    }

    public function test_operator_fetch_provider_failure_refunds_fee(): void
    {
        $this->enableService(PlanApiService::SERVICE_OPERATOR_FETCH, 0.10);
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'connect.ekychub.in/*' => Http::response([
                'status' => 'Failure',
                'message' => 'Please enter correct Mobile number ',
            ], 200),
        ]);

        $this->postJson(route('api.v1.plan.operator-fetch'), [
            'mobile' => '9468455123',
            'orderid' => 'OPF_FAIL',
        ])
            ->assertStatus(400)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Please enter correct Mobile number ');

        $this->assertEquals(100.0, (float) $this->user->fresh()->wallet_balance);
        $this->assertSame(2, WalletTransaction::query()->count());
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->user->id,
            'type' => 'credit',
        ]);
    }

    public function test_operator_fetch_missing_credentials(): void
    {
        Setting::setValue('ekychub_username', '', 'payment');
        Setting::setValue('ekychub_token', '', 'payment');
        $this->enableService(PlanApiService::SERVICE_OPERATOR_FETCH, 0.10);
        $this->actingAs($this->user, 'sanctum');

        $this->postJson(route('api.v1.plan.operator-fetch'), [
            'mobile' => '9468455123',
            'orderid' => 'OPF_NOCRED',
        ])
            ->assertStatus(400)
            ->assertJsonPath('status', 'error');

        $this->assertEquals(100.0, (float) $this->user->fresh()->wallet_balance);
    }

    public function test_operator_plan_fetch_success(): void
    {
        $this->enableService(PlanApiService::SERVICE_OPERATOR_PLAN_FETCH, 0.25);
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'connect.ekychub.in/*' => Http::response([
                'status' => 'Success',
                'Operator' => 'BSNL TOPUP',
                'message' => 'Operator Plan Successfully',
                'data' => [
                    'TOPUP' => [
                        ['rs' => 10, 'validity' => 'NA', 'desc' => 'Rs. 7.47 Talktime', 'Type' => 'talktime'],
                    ],
                ],
            ], 200),
        ]);

        $this->postJson(route('api.v1.plan.operator-plan-fetch'), [
            'mobile' => '9468455123',
            'opcode' => 'BT',
            'circle' => '96',
            'orderid' => 'PLN_OK',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.operator', 'BSNL TOPUP')
            ->assertJsonPath('fee', 0.25)
            ->assertJsonPath('wallet_balance', 99.75);
    }

    public function test_dth_info_success(): void
    {
        $this->enableService(PlanApiService::SERVICE_DTH_INFO, 0.15);
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'connect.ekychub.in/*' => Http::response([
                'status' => 'Success',
                'message' => 'DTH customer info Successfully checked',
                'data' => [
                    [
                        'VC' => '07210298754',
                        'Name' => 'Sarfaraz Nawaz',
                        'Balance' => '40.63',
                        'Minimum_recharge' => '200',
                    ],
                ],
            ], 200),
        ]);

        $this->postJson(route('api.v1.plan.dth-info'), [
            'dth_number' => '07210298754',
            'opcode' => 'ATV',
            'orderid' => 'DTHI_OK',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.customer.0.Name', 'Sarfaraz Nawaz')
            ->assertJsonPath('fee', 0.15);
    }

    public function test_dth_plan_fetch_success(): void
    {
        $this->enableService(PlanApiService::SERVICE_DTH_PLAN_FETCH, 0.20);
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'connect.ekychub.in/*' => Http::response([
                'status' => 'Success',
                'Operator' => 'AIRTEL DTH',
                'message' => 'DTH Operator Plan Fetch Successfully',
                'data' => [
                    'Combo' => [],
                ],
            ], 200),
        ]);

        $this->postJson(route('api.v1.plan.dth-plan-fetch'), [
            'dth_number' => '07210298754',
            'opcode' => 'ATV',
            'orderid' => 'DTHP_OK',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.operator', 'AIRTEL DTH')
            ->assertJsonPath('fee', 0.20);
    }

    public function test_zero_fee_does_not_create_wallet_transactions(): void
    {
        $this->enableService(PlanApiService::SERVICE_OPERATOR_FETCH, 0.0);
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'connect.ekychub.in/*' => Http::response([
                'status' => 'Success',
                'number' => '9468455xxx',
                'company' => 'BSNL',
                'circle' => 'Haryana',
                'circle_code' => '96',
                'message' => 'Operator fetched Successfully',
            ], 200),
        ]);

        $this->postJson(route('api.v1.plan.operator-fetch'), [
            'mobile' => '9468455123',
            'orderid' => 'OPF_FREE',
        ])
            ->assertOk()
            ->assertJsonPath('fee', 0);

        $this->assertEquals(100.0, (float) $this->user->fresh()->wallet_balance);
        $this->assertSame(0, WalletTransaction::query()->count());
    }

    public function test_whitelabel_plan_api_debits_user_fee_and_credits_wl_margin(): void
    {
        $whitelabel = \App\Models\Whitelabel::factory()->withFloat(100)->create();
        $developer = User::factory()->forWhitelabel($whitelabel->id)->create([
            'wallet_balance' => 50,
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $developer->assignRole(Role::Developer->value);

        \App\Models\WhitelabelPlanApiAccess::query()->create([
            'whitelabel_id' => $whitelabel->id,
            'service' => PlanApiService::SERVICE_OPERATOR_FETCH,
            'per_call_fee' => 0.10,
            'status' => true,
        ]);

        UserPlanApiAccess::query()->create([
            'user_id' => $developer->id,
            'service' => PlanApiService::SERVICE_OPERATOR_FETCH,
            'per_call_fee' => 0.15,
            'status' => true,
        ]);

        $this->actingAs($developer, 'sanctum');

        Http::fake([
            'connect.ekychub.in/*' => Http::response([
                'status' => 'Success',
                'number' => '9468455xxx',
                'company' => 'BSNL',
                'circle' => 'Haryana',
                'circle_code' => '96',
                'message' => 'Operator fetched Successfully',
            ], 200),
        ]);

        $this->postJson(route('api.v1.plan.operator-fetch'), [
            'mobile' => '9468455123',
            'orderid' => 'OPF_WL_MARGIN',
        ])
            ->assertOk()
            ->assertJsonPath('fee', 0.15)
            ->assertJsonPath('wallet_balance', 49.85);

        // User: 50 - 0.15
        $this->assertSame(49.85, (float) $developer->fresh()->wallet_balance);
        // WL: 100 - 0.15 + 0.05 margin = 99.90
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
    }

    public function test_whitelabel_plan_api_inactive_wl_service_returns_503(): void
    {
        $whitelabel = \App\Models\Whitelabel::factory()->withFloat(100)->create();
        $developer = User::factory()->forWhitelabel($whitelabel->id)->create([
            'wallet_balance' => 50,
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $developer->assignRole(Role::Developer->value);

        UserPlanApiAccess::query()->create([
            'user_id' => $developer->id,
            'service' => PlanApiService::SERVICE_OPERATOR_FETCH,
            'per_call_fee' => 0.15,
            'status' => true,
        ]);

        $this->actingAs($developer, 'sanctum');

        $this->postJson(route('api.v1.plan.operator-fetch'), [
            'mobile' => '9468455123',
            'orderid' => 'OPF_WL_OFF',
        ])
            ->assertStatus(503)
            ->assertJsonPath('code', 'SERVICE_UNAVAILABLE');

        $this->assertSame(50.0, (float) $developer->fresh()->wallet_balance);
        $this->assertSame(100.0, (float) $whitelabel->fresh()->wallet_balance);
    }
}
