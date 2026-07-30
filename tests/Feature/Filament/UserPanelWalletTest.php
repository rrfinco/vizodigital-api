<?php

namespace Tests\Feature\Filament;

use App\Enums\Role;
use App\Models\User;
use App\Services\Portal\PortalSettings;
use App\Filament\User\Pages\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class UserPanelWalletTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\CmsFoundationSeeder::class);

        // Configure mock credentials
        $settings = app(PortalSettings::class);
        $settings->set('rrfinco_account', 'TEST_ACCOUNT', 'payment');
        $settings->set('rrfinco_merchant_id', 'TEST_MERCHANT', 'payment');
        $settings->set('rrfinco_api_token', 'TEST_TOKEN', 'payment');
    }

    public function test_developer_can_access_wallet_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Developer->value);

        $this->actingAs($user)
            ->get('/user/wallet')
            ->assertOk();
    }

    public function test_developer_can_submit_add_funds_from_user_panel(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Developer->value);

        Http::fake([
            'pay.rrfinco.com/*' => Http::response([
                'status' => 'success',
                'payment_url' => 'https://pay.rrfinco.com/checkout/xyz123',
            ], 200)
        ]);

        Livewire::actingAs($user)
            ->test(Wallet::class)
            ->set('paymentMethod', 'online')
            ->set('amount', 250)
            ->call('submitPayment')
            ->assertRedirect('https://pay.rrfinco.com/checkout/xyz123');
    }
}
