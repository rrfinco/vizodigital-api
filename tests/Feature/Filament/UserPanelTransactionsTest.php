<?php

namespace Tests\Feature\Filament;

use App\Enums\Role;
use App\Filament\User\Pages\Transactions;
use App\Models\RechargeTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserPanelTransactionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\CmsFoundationSeeder::class);
    }

    public function test_developer_can_view_recharge_transactions_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Developer->value);

        RechargeTransaction::query()->create([
            'user_id' => $user->id,
            'client_request_id' => 'ORD_1',
            'api_request_id' => 'RC240101010101aabbcc',
            'operator_sp_key' => 116,
            'operator_type' => 'mobile',
            'account_number' => '9431023126',
            'amount' => 10,
            'commission_percentage' => 0.50,
            'commission_amount' => 0.05,
            'net_amount' => 10,
            'status' => 'success',
            'rpid' => 'RP1',
            'opid' => 'OP1',
        ]);

        RechargeTransaction::query()->create([
            'user_id' => $user->id,
            'client_request_id' => 'ORD_2',
            'api_request_id' => 'RC240101010102aabbcc',
            'operator_sp_key' => 3,
            'operator_type' => 'mobile',
            'account_number' => '6205705816',
            'amount' => 10,
            'commission_percentage' => 0.50,
            'commission_amount' => 0.05,
            'net_amount' => 10,
            'status' => 'failed',
            'error_message' => 'Same request can NOT accept within 15!',
        ]);

        $this->actingAs($user)
            ->get('/user/transactions')
            ->assertOk();

        Livewire::actingAs($user)
            ->test(Transactions::class)
            ->assertSee('Jio Prepaid')
            ->assertSee('Airtel Prepaid')
            ->assertSee('9431023126')
            ->assertSee('Success')
            ->assertSee('Failed')
            ->assertSee('Same request can NOT accept within 15!')
            ->assertDontSee('Reversal/Refund');
    }
}
