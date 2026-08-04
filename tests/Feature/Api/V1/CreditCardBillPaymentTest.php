<?php

namespace Tests\Feature\Api\V1;

use App\Enums\OnboardingStatus;
use App\Enums\Role;
use App\Models\BillPaymentTransaction;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserBillOperatorCommission;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CreditCardBillPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::create(['name' => Role::Developer->value]);

        $this->user = User::factory()->create([
            'wallet_balance' => 10000.0000,
            'onboarding_status' => OnboardingStatus::Approved,
        ]);
        $this->user->assignRole(Role::Developer->value);

        Setting::setValue('inspay_username', 'TEST_USER', 'payment');
        Setting::setValue('inspay_token', 'TEST_TOKEN', 'payment');
    }

    public function test_bill_fetch_requires_authentication(): void
    {
        $this->postJson(route('api.v1.bill-payment.credit-card.fetch'), [
            'mobile' => '9876543210',
            'card' => '3008',
            'opcode' => 'ICIC',
            'orderid' => 'ORD1',
        ])->assertUnauthorized();
    }

    public function test_bill_fetch_validation(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $this->postJson(route('api.v1.bill-payment.credit-card.fetch'), [
            'mobile' => '123',
            'card' => '',
            'opcode' => '',
            'orderid' => '',
        ])
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_bill_fetch_success(): void
    {
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'inspay.in/*' => Http::response([
                'status' => 'Success',
                'fetch_id' => 'TSB974ca11c49f641e08b17690a43631819',
                'customerName' => 'XXXX KUMAR AGARWAL',
                'billDate' => '08/12/2024',
                'billDueDate' => '28/12/2024',
                'billAmount' => '7475.12',
                'minimum_due' => '2344',
                'message' => 'Transaction Successful',
            ], 200),
        ]);

        $response = $this->postJson(route('api.v1.bill-payment.credit-card.fetch'), [
            'mobile' => '9876543210',
            'card' => '3008',
            'opcode' => 'ICIC',
            'orderid' => 'UNIQUE_ID_123',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.fetch_id', 'TSB974ca11c49f641e08b17690a43631819')
            ->assertJsonPath('data.bill_amount', 7475.12);

        $this->assertDatabaseHas('bill_payment_transactions', [
            'user_id' => $this->user->id,
            'type' => 'credit_card_fetch',
            'order_id' => 'UNIQUE_ID_123',
            'status' => 'success',
            'fetch_id' => 'TSB974ca11c49f641e08b17690a43631819',
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'credit_card/bill_fetch')
                && $request['username'] === 'TEST_USER'
                && $request['token'] === 'TEST_TOKEN'
                && $request['mobile'] === '9876543210';
        });
    }

    public function test_bill_pay_requires_pan_for_large_amount(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $this->postJson(route('api.v1.bill-payment.credit-card.pay'), [
            'mobile' => '9876543210',
            'card' => '3008',
            'amount' => 50000,
            'fetch_id' => 'FETCH123',
            'opcode' => 'ICIC',
            'orderid' => 'PAY_LARGE',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'PAN is mandatory for payments of ₹50,000 or more.');
    }

    public function test_bill_pay_insufficient_wallet(): void
    {
        $this->user->update(['wallet_balance' => 10]);
        $this->actingAs($this->user, 'sanctum');

        $this->postJson(route('api.v1.bill-payment.credit-card.pay'), [
            'mobile' => '9876543210',
            'card' => '3008',
            'amount' => 100,
            'fetch_id' => 'FETCH123',
            'opcode' => 'ICIC',
            'orderid' => 'PAY_LOW',
        ])
            ->assertStatus(400)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Insufficient wallet balance. Please recharge your wallet. Required: ₹100, Available: ₹10');
    }

    public function test_bill_pay_success_debits_wallet(): void
    {
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'inspay.in/*' => Http::response([
                'txid' => 51749154,
                'status' => 'Success',
                'utr' => 'TJ014363062020A6D7C1',
                'mobile' => '9876543210',
                'card' => 'xxxx3008',
                'dr_amount' => 7475.12,
                'message' => 'Transaction Successful',
                'orderid' => 'PAY_ID_123',
            ], 200),
        ]);

        $response = $this->postJson(route('api.v1.bill-payment.credit-card.pay'), [
            'mobile' => '9876543210',
            'card' => '3008',
            'amount' => 7475.12,
            'fetch_id' => 'TSB974ca11c49f641e08b17690a43631819',
            'opcode' => 'ICIC',
            'orderid' => 'PAY_ID_123',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.utr', 'TJ014363062020A6D7C1')
            ->assertJsonPath('data.txid', 51749154)
            ->assertJsonPath('data.commission_amount', 0);

        $this->assertEquals(2524.88, (float) $this->user->fresh()->wallet_balance);

        $this->assertDatabaseHas('bill_payment_transactions', [
            'order_id' => 'PAY_ID_123',
            'type' => 'credit_card_pay',
            'status' => 'success',
            'utr' => 'TJ014363062020A6D7C1',
        ]);

        $this->assertTrue(
            WalletTransaction::query()
                ->where('user_id', $this->user->id)
                ->where('type', 'debit')
                ->exists()
        );
    }

    public function test_bill_pay_percentage_commission_credited_on_success(): void
    {
        $this->actingAs($this->user, 'sanctum');

        UserBillOperatorCommission::create([
            'user_id' => $this->user->id,
            'opcode' => 'ICIC',
            'commission_type' => 'percentage',
            'commission_value' => 2.00,
            'status' => true,
        ]);

        Http::fake([
            'inspay.in/*' => Http::response([
                'txid' => 1001,
                'status' => 'Success',
                'utr' => 'UTR_PCT',
                'dr_amount' => 1000,
                'message' => 'Transaction Successful',
                'orderid' => 'PAY_PCT',
            ], 200),
        ]);

        $response = $this->postJson(route('api.v1.bill-payment.credit-card.pay'), [
            'mobile' => '9876543210',
            'card' => '3008',
            'amount' => 1000,
            'fetch_id' => 'FETCH_PCT',
            'opcode' => 'ICIC',
            'orderid' => 'PAY_PCT',
        ]);

        // 10000 - 1000 + 20 = 9020
        $response->assertOk()
            ->assertJsonPath('data.commission_amount', 20)
            ->assertJsonPath('data.wallet_balance', 9020);

        $this->user->refresh();
        $this->assertEquals(9020.0, (float) $this->user->wallet_balance);
        $this->assertEquals(20.0, (float) $this->user->earning_balance);

        $this->assertDatabaseHas('bill_payment_transactions', [
            'order_id' => 'PAY_PCT',
            'commission_type' => 'percentage',
            'commission_value' => 2.00,
            'commission_amount' => 20.00,
            'status' => 'success',
        ]);
    }

    public function test_bill_pay_flat_commission_credited_on_success(): void
    {
        $this->actingAs($this->user, 'sanctum');

        UserBillOperatorCommission::create([
            'user_id' => $this->user->id,
            'opcode' => 'ICIC',
            'commission_type' => 'flat',
            'commission_value' => 15.00,
            'status' => true,
        ]);

        Http::fake([
            'inspay.in/*' => Http::response([
                'txid' => 1002,
                'status' => 'Success',
                'utr' => 'UTR_FLAT',
                'dr_amount' => 500,
                'message' => 'Transaction Successful',
                'orderid' => 'PAY_FLAT',
            ], 200),
        ]);

        $this->postJson(route('api.v1.bill-payment.credit-card.pay'), [
            'mobile' => '9876543210',
            'card' => '3008',
            'amount' => 500,
            'fetch_id' => 'FETCH_FLAT',
            'opcode' => 'ICIC',
            'orderid' => 'PAY_FLAT',
        ])
            ->assertOk()
            ->assertJsonPath('data.commission_amount', 15)
            ->assertJsonPath('data.wallet_balance', 9515);

        $this->user->refresh();
        $this->assertEquals(9515.0, (float) $this->user->wallet_balance);
        $this->assertEquals(15.0, (float) $this->user->earning_balance);
    }

    public function test_bill_pay_inactive_operator_rejected(): void
    {
        $this->actingAs($this->user, 'sanctum');

        UserBillOperatorCommission::create([
            'user_id' => $this->user->id,
            'opcode' => 'ICIC',
            'commission_type' => 'percentage',
            'commission_value' => 2.00,
            'status' => false,
        ]);

        $this->postJson(route('api.v1.bill-payment.credit-card.pay'), [
            'mobile' => '9876543210',
            'card' => '3008',
            'amount' => 100,
            'fetch_id' => 'FETCH_INACTIVE',
            'opcode' => 'ICIC',
            'orderid' => 'PAY_INACTIVE',
        ])
            ->assertStatus(400)
            ->assertJsonPath('message', 'This operator is currently inactive or disabled for your account.');

        $this->assertEquals(10000.0, (float) $this->user->fresh()->wallet_balance);
    }

    public function test_bill_pay_failure_does_not_credit_commission(): void
    {
        $this->actingAs($this->user, 'sanctum');

        UserBillOperatorCommission::create([
            'user_id' => $this->user->id,
            'opcode' => 'ICIC',
            'commission_type' => 'flat',
            'commission_value' => 25.00,
            'status' => true,
        ]);

        Http::fake([
            'inspay.in/*' => Http::response([
                'status' => 'Failed',
                'message' => 'Operator timeout',
            ], 200),
        ]);

        $this->postJson(route('api.v1.bill-payment.credit-card.pay'), [
            'mobile' => '9876543210',
            'card' => '3008',
            'amount' => 500,
            'fetch_id' => 'FETCH_FAIL_COMM',
            'opcode' => 'ICIC',
            'orderid' => 'PAY_FAIL_COMM',
        ])
            ->assertStatus(400);

        $this->user->refresh();
        $this->assertEquals(10000.0, (float) $this->user->wallet_balance);
        $this->assertEquals(0.0, (float) $this->user->earning_balance);
    }

    public function test_bill_pay_failure_refunds_wallet(): void
    {
        $this->actingAs($this->user, 'sanctum');

        Http::fake([
            'inspay.in/*' => Http::response([
                'status' => 'Failed',
                'message' => 'Operator timeout',
            ], 200),
        ]);

        $this->postJson(route('api.v1.bill-payment.credit-card.pay'), [
            'mobile' => '9876543210',
            'card' => '3008',
            'amount' => 500,
            'fetch_id' => 'FETCH_FAIL',
            'opcode' => 'ICIC',
            'orderid' => 'PAY_FAIL_1',
        ])
            ->assertStatus(400)
            ->assertJsonPath('status', 'error');

        $this->assertEquals(10000.0, (float) $this->user->fresh()->wallet_balance);

        $txn = BillPaymentTransaction::query()->where('order_id', 'PAY_FAIL_1')->first();
        $this->assertNotNull($txn);
        $this->assertEquals('failed', $txn->status);
    }
}
