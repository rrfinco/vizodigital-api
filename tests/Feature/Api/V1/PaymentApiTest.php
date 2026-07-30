<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Deposit;
use App\Services\Portal\PortalSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentApiTest extends TestCase
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

    public function test_api_payment_requires_authentication(): void
    {
        $response = $this->postJson(route('api.v1.payment.create'), [
            'amount' => 100
        ]);

        $response->assertStatus(401);
    }

    public function test_api_payment_validation_fails_for_invalid_amount(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson(route('api.v1.payment.create'), [
            'amount' => 'invalid-amount'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_api_payment_initiation_success(): void
    {
        $this->actingAs($this->user, 'sanctum');

        // Mock gateway response
        Http::fake([
            'pay.rrfinco.com/*' => Http::response([
                'status' => 'success',
                'payment_url' => 'https://pay.rrfinco.com/checkout/xyz123',
            ], 200)
        ]);

        $response = $this->postJson(route('api.v1.payment.create'), [
            'amount' => 150
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('payment_url', 'https://pay.rrfinco.com/checkout/xyz123');

        // Verify deposit record logged
        $deposit = Deposit::first();
        $this->assertNotNull($deposit);
        $this->assertEquals($this->user->id, $deposit->user_id);
        $this->assertEquals(150.0000, (float) $deposit->amount);
        $this->assertEquals('pending', $deposit->status);
    }
}
