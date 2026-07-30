<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\UserOperatorCommission;
use App\Models\RechargeTransaction;
use App\Enums\Role;
use App\Enums\OnboardingStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RechargeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the role first
        \Spatie\Permission\Models\Role::create(['name' => Role::Developer->value]);

        // Create a developer role user
        $this->user = User::factory()->create([
            'wallet_balance' => 500.0000,
            'onboarding_status' => OnboardingStatus::Approved,
        ]);

        $this->user->assignRole(Role::Developer->value);

        // Seed some admin settings for Roundpay
        \App\Models\Setting::setValue('roundpay_api_url', 'https://api.roundpay.net/API/TransactionAPI');
        \App\Models\Setting::setValue('roundpay_user_id', 'TESTUSER');
        \App\Models\Setting::setValue('roundpay_token', 'TESTTOKEN');
    }

    public function test_recharge_requires_authentication(): void
    {
        $response = $this->postJson(route('api.v1.recharge'), [
            'account_number' => '9876543210',
            'amount' => 100,
            'operator_sp_key' => 116,
            'operator_type' => 'mobile',
        ]);

        $response->assertStatus(401);
    }

    public function test_recharge_validation(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson(route('api.v1.recharge'), [
            'account_number' => '',
            'amount' => -10,
            'operator_sp_key' => 'invalid',
            'operator_type' => 'invalid-type',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['status', 'message', 'errors']);
    }

    public function test_recharge_insufficient_balance(): void
    {
        $this->actingAs($this->user, 'sanctum');

        // Set wallet balance to low amount
        $this->user->update(['wallet_balance' => 10.0000]);

        $response = $this->postJson(route('api.v1.recharge'), [
            'account_number' => '9876543210',
            'amount' => 100,
            'operator_sp_key' => 116, // Jio (default commission 3%) => net ₹97
            'operator_type' => 'mobile',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Insufficient wallet balance. Required: ₹100, Available: ₹10');
    }

    public function test_recharge_success(): void
    {
        $this->actingAs($this->user, 'sanctum');

        // Mock Roundpay Success response
        Http::fake([
            'api.roundpay.net/*' => Http::response([
                'STATUS' => '2',
                'MSG' => 'SUCCESS',
                'BAL' => '12846.22',
                'ERRORCODE' => '200',
                'ACCOUNT' => '9876543210',
                'AMOUNT' => '100',
                'RPID' => 'RPID_SUCCESS_123',
                'AGENTID' => '8822499',
                'OPID' => 'OPID_SUCCESS_456'
            ], 200)
        ]);

        $response = $this->postJson(route('api.v1.recharge'), [
            'account_number' => '9876543210',
            'amount' => 100,
            'operator_sp_key' => 116, // Jio (default commission 3% => net ₹97)
            'operator_type' => 'mobile',
            'client_request_id' => 'CLIENT_TXN_001',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.amount', 100)
            ->assertJsonPath('data.operator_ref', 'OPID_SUCCESS_456')
            ->assertJsonPath('data.provider_txn_id', 'RPID_SUCCESS_123');

        // Check wallet balance: 500 - 100 + 3 = 403 (debit full 100, credit 3 commission)
        $this->user->refresh();
        $this->assertEquals(403.0000, (float) $this->user->wallet_balance);
        $this->assertEquals(3.0000, (float) $this->user->earning_balance);

        // Verify transaction logged
        $txn = RechargeTransaction::first();
        $this->assertNotNull($txn);
        $this->assertEquals('success', $txn->status);
        $this->assertEquals('RPID_SUCCESS_123', $txn->rpid);
    }

    public function test_recharge_pending(): void
    {
        $this->actingAs($this->user, 'sanctum');

        // Mock Roundpay Pending response
        Http::fake([
            'api.roundpay.net/*' => Http::response([
                'STATUS' => '1',
                'MSG' => 'PENDING',
                'BAL' => '12846.22',
                'ERRORCODE' => '200',
                'ACCOUNT' => '9876543210',
                'AMOUNT' => '100',
                'RPID' => 'RPID_PENDING_123'
            ], 200)
        ]);

        $response = $this->postJson(route('api.v1.recharge'), [
            'account_number' => '9876543210',
            'amount' => 100,
            'operator_sp_key' => 51, // Airtel DTH (default commission 3% => net ₹97)
            'operator_type' => 'dth',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('data.provider_txn_id', 'RPID_PENDING_123');

        // Check wallet balance: 500 - 100 = 400 (pending, commission not credited yet)
        $this->user->refresh();
        $this->assertEquals(400.0000, (float) $this->user->wallet_balance);
        $this->assertEquals(0.0000, (float) $this->user->earning_balance);
    }

    public function test_recharge_failed_refunds_wallet(): void
    {
        $this->actingAs($this->user, 'sanctum');

        // Mock Roundpay Failed response
        Http::fake([
            'api.roundpay.net/*' => Http::response([
                'STATUS' => '3',
                'MSG' => 'Operator Down or Invalid Account',
                'ERRORCODE' => '501'
            ], 200)
        ]);

        $response = $this->postJson(route('api.v1.recharge'), [
            'account_number' => '9876543210',
            'amount' => 100,
            'operator_sp_key' => 116, // Jio (default commission 3% => net ₹97)
            'operator_type' => 'mobile',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('status', 'failed');

        // Check wallet balance is refunded (should remain 500)
        $this->user->refresh();
        $this->assertEquals(500.0000, (float) $this->user->wallet_balance);

        // Check transaction status is failed
        $txn = RechargeTransaction::first();
        $this->assertEquals('failed', $txn->status);
    }

    public function test_recharge_custom_commission_applied(): void
    {
        $this->actingAs($this->user, 'sanctum');

        // Create a custom commission configuration of 5% for Jio Prepaid (SPKey 116)
        UserOperatorCommission::create([
            'user_id' => $this->user->id,
            'operator_type' => 'mobile',
            'operator_sp_key' => 116,
            'commission_percentage' => 5.00,
            'status' => true,
        ]);

        Http::fake([
            'api.roundpay.net/*' => Http::response([
                'STATUS' => '2',
                'MSG' => 'SUCCESS',
                'RPID' => 'RPID_SUCCESS_999'
            ], 200)
        ]);

        $response = $this->postJson(route('api.v1.recharge'), [
            'account_number' => '9876543210',
            'amount' => 100,
            'operator_sp_key' => 116,
            'operator_type' => 'mobile',
        ]);

        // Net debit should be: 100 - 5% = ₹95
        $response->assertStatus(200);

        $this->user->refresh();
        $this->assertEquals(405.0000, (float) $this->user->wallet_balance);
        $this->assertEquals(5.0000, (float) $this->user->earning_balance);
    }

    public function test_recharge_custom_geocode_pincode_customer_number_forwarded(): void
    {
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'api.roundpay.net/*' => function (\Illuminate\Http\Client\Request $request) {
                $urlParts = parse_url($request->url());
                parse_str($urlParts['query'] ?? '', $query);

                $this->assertEquals('12.34,56.78', $query['GEOCode'] ?? null);
                $this->assertEquals('112233', $query['Pincode'] ?? null);
                $this->assertEquals('8888888888', $query['CustomerNumber'] ?? null);

                return Http::response([
                    'STATUS' => '2',
                    'MSG' => 'SUCCESS',
                    'RPID' => 'RPID_SUCCESS_123',
                ], 200);
            }
        ]);

        $response = $this->postJson(route('api.v1.recharge'), [
            'account_number' => '9876543210',
            'amount' => 100,
            'operator_sp_key' => 116,
            'operator_type' => 'mobile',
            'geocode' => '12.34,56.78',
            'pincode' => '112233',
            'customer_number' => '8888888888',
        ]);

        $response->assertStatus(200);
    }
}
