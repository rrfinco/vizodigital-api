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
            ->assertJsonPath('message', 'Insufficient wallet balance. Please recharge your wallet. Required: ₹100, Available: ₹10');
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

    public function test_recharge_success_with_lowercase_roundpay_keys(): void
    {
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'api.roundpay.net/*' => Http::response([
                'status' => 2,
                'msg' => 'SUCCESS',
                'bal' => '12846.22',
                'errorcode' => '200',
                'account' => '9876543210',
                'amount' => '100',
                'rpid' => 'RPID_LC_123',
                'agentid' => '8822499',
                'opid' => 'OPID_LC_456',
            ], 200),
        ]);

        $this->postJson(route('api.v1.recharge'), [
            'account_number' => '9876543210',
            'amount' => 100,
            'operator_sp_key' => 116,
            'operator_type' => 'mobile',
            'client_request_id' => 'CLIENT_TXN_LC',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.operator_ref', 'OPID_LC_456')
            ->assertJsonPath('data.provider_txn_id', 'RPID_LC_123');

        $this->assertEquals('success', RechargeTransaction::query()->where('client_request_id', 'CLIENT_TXN_LC')->value('status'));
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

    public function test_recharge_rejects_duplicate_client_request_id_for_same_user(): void
    {
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'api.roundpay.net/*' => Http::response([
                'STATUS' => '2',
                'MSG' => 'SUCCESS',
                'BAL' => '1000',
                'ERRORCODE' => '200',
                'RPID' => 'RPID_DUP_1',
                'OPID' => 'OPID_DUP_1',
            ], 200),
        ]);

        $payload = [
            'account_number' => '9876543210',
            'amount' => 10,
            'operator_sp_key' => 116,
            'operator_type' => 'mobile',
            'client_request_id' => 'UNIQUE_ORDER_42',
        ];

        $this->postJson(route('api.v1.recharge'), $payload)
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->postJson(route('api.v1.recharge'), $payload)
            ->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'This client_request_id was already used. Provide a unique order ID.');

        $this->assertSame(1, RechargeTransaction::query()->where('client_request_id', 'UNIQUE_ORDER_42')->count());
        Http::assertSentCount(1);
    }

    public function test_mokshiq_requires_circle(): void
    {
        $this->user->update(['recharge_provider' => \App\Enums\RechargeProvider::Mokshiq]);
        $this->actingAs($this->user, 'sanctum');

        \App\Models\Setting::setValue('mokshiq_token', 'MK_TOKEN', 'recharge');
        \App\Models\Setting::setValue('mokshiq_pin', '2242', 'recharge');
        \App\Models\Setting::setValue('mokshiq_origin', 'https://partner.example.com', 'recharge');

        $this->postJson(route('api.v1.recharge'), [
            'account_number' => '9876543210',
            'amount' => 10,
            'operator_sp_key' => 116,
            'operator_type' => 'mobile',
            'client_request_id' => 'MK_NO_CIRCLE',
        ])
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');

        Http::assertNothingSent();
    }

    public function test_mokshiq_success_dth_debits_wallet(): void
    {
        $this->user->update(['recharge_provider' => \App\Enums\RechargeProvider::Mokshiq]);
        $this->actingAs($this->user, 'sanctum');

        \App\Models\Setting::setValue('mokshiq_token', 'MK_TOKEN', 'recharge');
        \App\Models\Setting::setValue('mokshiq_pin', '2242', 'recharge');
        \App\Models\Setting::setValue('mokshiq_origin', 'https://partner.example.com', 'recharge');

        Http::fake([
            'api.mokshiq.in/*' => Http::response([
                'status' => 'success',
                'message' => 'Recharge Successful',
                'txn_id' => 'MK_DTH_TXN_001',
                'opid' => 'MK_DTH_OP_001',
            ], 200),
        ]);

        $this->postJson(route('api.v1.recharge'), [
            'account_number' => '1234567890',
            'amount' => 100,
            'operator_sp_key' => 51, // Airtel Digital TV
            'operator_type' => 'dth',
            'client_request_id' => 'MK_DTH_OK_1',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.provider_txn_id', 'MK_DTH_TXN_001')
            ->assertJsonPath('data.operator_ref', 'MK_DTH_OP_001');

        $this->user->refresh();
        // Default commission on Airtel DTH is 3% => net debit 97
        $this->assertEquals(403.0000, (float) $this->user->wallet_balance);

        $txn = RechargeTransaction::query()->where('client_request_id', 'MK_DTH_OK_1')->first();
        $this->assertNotNull($txn);
        $this->assertEquals(\App\Enums\RechargeProvider::Mokshiq, $txn->provider);
        $this->assertNull($txn->circle);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'create_dth_recharge')
                && $request->hasHeader('Authorization', 'Bearer MK_TOKEN')
                && $request->hasHeader('Origin', 'https://partner.example.com');
        });
    }

    public function test_mokshiq_success_debits_wallet(): void
    {
        $this->user->update(['recharge_provider' => \App\Enums\RechargeProvider::Mokshiq]);
        $this->actingAs($this->user, 'sanctum');

        \App\Models\Setting::setValue('mokshiq_token', 'MK_TOKEN', 'recharge');
        \App\Models\Setting::setValue('mokshiq_pin', '2242', 'recharge');
        \App\Models\Setting::setValue('mokshiq_origin', 'https://partner.example.com', 'recharge');

        Http::fake([
            'api.mokshiq.in/*' => Http::response([
                'status' => 'success',
                'message' => 'Recharge Successful',
                'txn_id' => 'MK_TXN_001',
                'opid' => 'MK_OP_001',
            ], 200),
        ]);

        $this->postJson(route('api.v1.recharge'), [
            'account_number' => '9876543210',
            'amount' => 100,
            'operator_sp_key' => 116,
            'operator_type' => 'mobile',
            'circle' => 'Bihar and Jharkhand',
            'client_request_id' => 'MK_OK_1',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.provider_txn_id', 'MK_TXN_001')
            ->assertJsonPath('data.operator_ref', 'MK_OP_001');

        $this->user->refresh();
        $this->assertEquals(403.0000, (float) $this->user->wallet_balance);

        $txn = RechargeTransaction::query()->where('client_request_id', 'MK_OK_1')->first();
        $this->assertNotNull($txn);
        $this->assertEquals('success', $txn->status);
        $this->assertEquals(\App\Enums\RechargeProvider::Mokshiq, $txn->provider);
        $this->assertEquals('Bihar and Jharkhand', $txn->circle);

        Http::assertSent(function ($request) {
            $body = $request->body();

            return str_contains($request->url(), 'create_mobile_recharge')
                && $request->hasHeader('Authorization', 'Bearer MK_TOKEN')
                && $request->hasHeader('Origin', 'https://partner.example.com')
                && str_contains($body, 'name="circle"')
                && str_contains($body, 'Bihar Jharkhand')
                && ! str_contains($body, 'Bihar and Jharkhand');
        });
    }

    public function test_mokshiq_normalizes_operator_fetch_circle_name(): void
    {
        $this->user->update(['recharge_provider' => \App\Enums\RechargeProvider::Mokshiq]);
        $this->actingAs($this->user, 'sanctum');

        \App\Models\Setting::setValue('mokshiq_token', 'MK_TOKEN', 'recharge');
        \App\Models\Setting::setValue('mokshiq_pin', '2242', 'recharge');
        \App\Models\Setting::setValue('mokshiq_origin', 'https://api.vizodigital.com/', 'recharge');

        Http::fake([
            'api.mokshiq.in/*' => Http::response([
                'status' => 'success',
                'message' => 'Recharge Successful',
                'txn_id' => 'MK_TXN_CIRCLE',
                'opid' => 'MK_OP_CIRCLE',
            ], 200),
        ]);

        $this->postJson(route('api.v1.recharge'), [
            'account_number' => '6205705816',
            'amount' => 10,
            'operator_sp_key' => 3,
            'operator_type' => 'mobile',
            'circle' => 'Bihar and Jharkhand',
            'client_request_id' => 'MK_CIRCLE_NORM',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $txn = RechargeTransaction::query()->where('client_request_id', 'MK_CIRCLE_NORM')->first();
        $this->assertNotNull($txn);
        // Persist what the client sent; only the outbound Mokshiq payload is normalized.
        $this->assertEquals('Bihar and Jharkhand', $txn->circle);

        Http::assertSent(function ($request) {
            $body = $request->body();

            return str_contains($request->url(), 'create_mobile_recharge')
                && $request->hasHeader('Origin', 'https://api.vizodigital.com')
                && str_contains($body, 'name="operator"')
                && str_contains($body, 'Airtel')
                && str_contains($body, 'name="circle"')
                && str_contains($body, 'Bihar Jharkhand')
                && ! str_contains($body, 'Bihar and Jharkhand');
        });
    }

    public function test_whitelabel_developer_inherits_mokshiq_provider(): void
    {
        $wl = \App\Models\Whitelabel::factory()->withFloat(5000)->create([
            'recharge_provider' => \App\Enums\RechargeProvider::Mokshiq,
        ]);

        $this->user->update([
            'whitelabel_id' => $wl->id,
            'recharge_provider' => \App\Enums\RechargeProvider::Roundpay, // ignored for WL users
        ]);

        \App\Models\Setting::setValue('mokshiq_token', 'MK_TOKEN', 'recharge');
        \App\Models\Setting::setValue('mokshiq_pin', '2242', 'recharge');
        \App\Models\Setting::setValue('mokshiq_origin', 'https://partner.example.com', 'recharge');

        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'api.mokshiq.in/*' => Http::response([
                'status' => 'success',
                'message' => 'OK',
                'txn_id' => 'MK_WL_1',
            ], 200),
            'api.roundpay.net/*' => Http::response(['STATUS' => '2', 'MSG' => 'SHOULD_NOT_HIT'], 200),
        ]);

        $this->postJson(route('api.v1.recharge'), [
            'account_number' => '9876543210',
            'amount' => 10,
            'operator_sp_key' => 116,
            'operator_type' => 'mobile',
            'circle' => 'Delhi NCR',
            'client_request_id' => 'MK_WL_OK',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.mokshiq.in'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.roundpay.net'));
    }

    public function test_roundpay_ignores_circle(): void
    {
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'api.roundpay.net/*' => Http::response([
                'STATUS' => '2',
                'MSG' => 'SUCCESS',
                'RPID' => 'RP_CIRCLE_IGN',
                'OPID' => 'OP_CIRCLE_IGN',
            ], 200),
        ]);

        $this->postJson(route('api.v1.recharge'), [
            'account_number' => '9876543210',
            'amount' => 10,
            'operator_sp_key' => 116,
            'operator_type' => 'mobile',
            'circle' => 'Bihar',
            'client_request_id' => 'RP_WITH_CIRCLE',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $txn = RechargeTransaction::query()->where('client_request_id', 'RP_WITH_CIRCLE')->first();
        $this->assertEquals(\App\Enums\RechargeProvider::Roundpay, $txn->provider);
        $this->assertEquals('Bihar', $txn->circle);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.roundpay.net'));
    }
}
