<?php

namespace Tests\Feature\Whitelabel;

use App\Enums\OnboardingStatus;
use App\Enums\Role;
use App\Enums\WhitelabelStatus;
use App\Models\Setting;
use App\Models\User;
use App\Models\Whitelabel;
use App\Models\WhitelabelOperatorCommission;
use App\Models\WhitelabelWalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhitelabelBillingGateTest extends TestCase
{
    use RefreshDatabase;

    protected User $developer;

    protected Whitelabel $whitelabel;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::create(['name' => Role::Developer->value]);

        $this->whitelabel = Whitelabel::factory()->withFloat(1000)->create();

        $this->developer = User::factory()->forWhitelabel($this->whitelabel->id)->create([
            'wallet_balance' => 500,
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $this->developer->assignRole(Role::Developer->value);

        Setting::setValue('roundpay_api_url', 'https://api.roundpay.net/API/TransactionAPI');
        Setting::setValue('roundpay_user_id', 'TESTUSER');
        Setting::setValue('roundpay_token', 'TESTTOKEN');
    }

    public function test_float_exhausted_returns_503_without_touching_developer_wallet(): void
    {
        $this->whitelabel->update(['wallet_balance' => 10]);
        $this->actingAs($this->developer, 'sanctum');

        $response = $this->postJson(route('api.v1.recharge'), [
            'account_number' => '9876543210',
            'amount' => 100,
            'operator_sp_key' => 116,
            'operator_type' => 'mobile',
        ]);

        $response->assertStatus(503)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('code', 'SERVICE_UNAVAILABLE')
            ->assertJsonPath('message', 'Service temporarily unavailable. Please try again later.');

        $this->assertSame(500.0, (float) $this->developer->fresh()->wallet_balance);
        $this->assertSame(10.0, (float) $this->whitelabel->fresh()->wallet_balance);
        $this->assertDatabaseCount('recharge_transactions', 0);
    }

    public function test_suspended_whitelabel_returns_503_even_with_float(): void
    {
        $this->whitelabel->update([
            'status' => WhitelabelStatus::Suspended,
            'wallet_balance' => 5000,
        ]);
        $this->actingAs($this->developer, 'sanctum');

        $response = $this->postJson(route('api.v1.recharge'), [
            'account_number' => '9876543210',
            'amount' => 100,
            'operator_sp_key' => 116,
            'operator_type' => 'mobile',
        ]);

        $response->assertStatus(503)
            ->assertJsonPath('code', 'SERVICE_UNAVAILABLE');

        $this->assertSame(500.0, (float) $this->developer->fresh()->wallet_balance);
    }

    public function test_successful_recharge_debits_both_and_credits_both_commissions(): void
    {
        WhitelabelOperatorCommission::query()->create([
            'whitelabel_id' => $this->whitelabel->id,
            'operator_type' => 'mobile',
            'operator_sp_key' => 116,
            'commission_percentage' => 1,
            'status' => true,
        ]);

        $this->actingAs($this->developer, 'sanctum');

        Http::fake([
            'api.roundpay.net/*' => Http::response([
                'STATUS' => '2',
                'MSG' => 'SUCCESS',
                'ERRORCODE' => '200',
                'RPID' => 'RPID_WL_1',
                'OPID' => 'OPID_WL_1',
            ], 200),
        ]);

        $response = $this->postJson(route('api.v1.recharge'), [
            'account_number' => '9876543210',
            'amount' => 100,
            'operator_sp_key' => 116, // default user commission 3%
            'operator_type' => 'mobile',
        ]);

        $response->assertStatus(200)->assertJsonPath('status', 'success');

        // Dev: 500 - 100 + 3 = 403
        $this->assertSame(403.0, (float) $this->developer->fresh()->wallet_balance);
        // WL: 1000 - 100 + 1 = 901
        $this->assertSame(901.0, (float) $this->whitelabel->fresh()->wallet_balance);

        $this->assertDatabaseHas('whitelabel_wallet_transactions', [
            'whitelabel_id' => $this->whitelabel->id,
            'type' => 'debit',
        ]);
        $this->assertDatabaseHas('whitelabel_wallet_transactions', [
            'whitelabel_id' => $this->whitelabel->id,
            'type' => 'credit',
        ]);
        $this->assertSame(2, WhitelabelWalletTransaction::query()->where('whitelabel_id', $this->whitelabel->id)->count());
    }

    public function test_failed_recharge_refunds_developer_and_whitelabel(): void
    {
        $this->actingAs($this->developer, 'sanctum');

        Http::fake([
            'api.roundpay.net/*' => Http::response([
                'STATUS' => '3',
                'MSG' => 'FAILED',
                'ERRORCODE' => '500',
            ], 200),
        ]);

        $response = $this->postJson(route('api.v1.recharge'), [
            'account_number' => '9876543210',
            'amount' => 100,
            'operator_sp_key' => 116,
            'operator_type' => 'mobile',
        ]);

        $response->assertStatus(400)->assertJsonPath('status', 'failed');

        $this->assertSame(500.0, (float) $this->developer->fresh()->wallet_balance);
        $this->assertSame(1000.0, (float) $this->whitelabel->fresh()->wallet_balance);
    }

    public function test_b2c_developer_without_whitelabel_unchanged(): void
    {
        $b2c = User::factory()->create([
            'wallet_balance' => 500,
            'onboarding_status' => OnboardingStatus::Approved,
            'whitelabel_id' => null,
        ]);
        $b2c->assignRole(Role::Developer->value);
        $this->actingAs($b2c, 'sanctum');

        Http::fake([
            'api.roundpay.net/*' => Http::response([
                'STATUS' => '2',
                'MSG' => 'SUCCESS',
                'ERRORCODE' => '200',
                'RPID' => 'RPID_B2C',
                'OPID' => 'OPID_B2C',
            ], 200),
        ]);

        $response = $this->postJson(route('api.v1.recharge'), [
            'account_number' => '9876543210',
            'amount' => 100,
            'operator_sp_key' => 116,
            'operator_type' => 'mobile',
        ]);

        $response->assertStatus(200)->assertJsonPath('status', 'success');
        $this->assertSame(403.0, (float) $b2c->fresh()->wallet_balance);
        $this->assertDatabaseCount('whitelabel_wallet_transactions', 0);
    }
}
