<?php

namespace Tests\Feature\Web;

use App\Models\User;
use App\Models\Deposit;
use App\Models\WalletTransaction;
use App\Services\Portal\PortalSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'John Developer',
            'email' => 'john@example.com',
            'wallet_balance' => 500.0000,
            'onboarding_status' => 'approved',
        ]);

        // Configure mock credentials
        $settings = app(PortalSettings::class);
        $settings->set('rrfinco_account', 'TEST_ACCOUNT', 'payment');
        $settings->set('rrfinco_merchant_id', 'TEST_MERCHANT', 'payment');
        $settings->set('rrfinco_api_token', 'TEST_TOKEN', 'payment');
    }

    public function test_payment_requires_authentication(): void
    {
        $response = $this->post(route('payment.initiate'), [
            'amount' => 100
        ]);

        $response->assertRedirect('/login');
    }

    public function test_payment_validation_fails_for_invalid_amount(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('payment.initiate'), [
            'amount' => 'invalid-amount'
        ]);

        $response->assertSessionHasErrors(['amount']);

        $response = $this->post(route('payment.initiate'), [
            'amount' => 0
        ]);

        $response->assertSessionHasErrors(['amount']);
    }

    public function test_payment_initiation_success(): void
    {
        $this->actingAs($this->user);

        // Mock gateway response
        Http::fake([
            'pay.rrfinco.com/*' => Http::response([
                'status' => 'success',
                'payment_url' => 'https://pay.rrfinco.com/checkout/xyz123',
            ], 200)
        ]);

        $response = $this->post(route('payment.initiate'), [
            'amount' => 150
        ]);

        // Assert redirect to gateway checkout page
        $response->assertRedirect('https://pay.rrfinco.com/checkout/xyz123');

        // Verify deposit record logged
        $deposit = Deposit::first();
        $this->assertNotNull($deposit);
        $this->assertEquals($this->user->id, $deposit->user_id);
        $this->assertEquals(150.0000, (float) $deposit->amount);
        $this->assertEquals('pending', $deposit->status);
        $this->assertStringStartsWith('ORDER_', $deposit->order_id);
    }

    public function test_payment_sends_ten_digit_phone_to_gateway(): void
    {
        $this->user->update(['phone' => '+91 98765-43210']);
        $this->actingAs($this->user);

        Http::fake([
            'pay.rrfinco.com/*' => Http::response([
                'status' => 'success',
                'payment_url' => 'https://pay.rrfinco.com/checkout/xyz123',
            ], 200)
        ]);

        $this->post(route('payment.initiate'), [
            'amount' => 100,
        ])->assertRedirect('https://pay.rrfinco.com/checkout/xyz123');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://pay.rrfinco.com/api/v1/payment/create'
                && ($request['cust_phone'] ?? null) === '9876543210';
        });
    }

    public function test_payment_webhook_credits_user_wallet(): void
    {
        $deposit = Deposit::create([
            'user_id' => $this->user->id,
            'order_id' => 'ORDER_TEST_999',
            'amount' => 200.0000,
            'status' => 'pending',
        ]);

        Http::fake([
            'pay.rrfinco.com/api/v1/payment/status/ORDER_TEST_999' => Http::response([
                'status' => 'Success',
                'txn_id' => 'TXN_GATEWAY_789',
                'amount' => '200.00',
            ], 200)
        ]);

        $response = $this->postJson(route('payment.webhook'), [
            'order_id' => 'ORDER_TEST_999',
            'status' => 'Success',
            'txn_id' => 'TXN_GATEWAY_789',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'processed');

        // Verify deposit updated to success
        $deposit->refresh();
        $this->assertEquals('success', $deposit->status);
        $this->assertEquals('TXN_GATEWAY_789', $deposit->gateway_ref);

        // Verify user wallet credited: 500 + 200 = 700
        $this->user->refresh();
        $this->assertEquals(700.0000, (float) $this->user->wallet_balance);

        // Verify wallet transaction logged
        $txn = WalletTransaction::first();
        $this->assertNotNull($txn);
        $this->assertEquals(200.0000, (float) $txn->amount);
        $this->assertEquals('credit', $txn->type);
        $this->assertEquals($deposit->id, $txn->reference_id);
    }
}
