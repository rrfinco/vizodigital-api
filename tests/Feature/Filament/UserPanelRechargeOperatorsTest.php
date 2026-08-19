<?php

namespace Tests\Feature\Filament;

use App\Enums\Role;
use App\Filament\User\Pages\RechargeOperators;
use App\Filament\User\Widgets\DeveloperShortcodesWidget;
use App\Models\User;
use App\Models\UserOperatorCommission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserPanelRechargeOperatorsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_dashboard_popular_operators_use_canonical_sp_keys(): void
    {
        $codes = collect(app(DeveloperShortcodesWidget::class)->getPopularShortcodes());

        $this->assertSame('3', $codes->firstWhere('name', 'Airtel Prepaid')['sp_key']);
        $this->assertSame('116', $codes->firstWhere('name', 'Jio Prepaid')['sp_key']);
        $this->assertCount($codes->count(), $codes->pluck('sp_key')->unique());
    }

    public function test_developer_can_access_recharge_operators_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Developer->value);

        $this->actingAs($user)
            ->get('/user/recharge-operators')
            ->assertOk()
            ->assertSee('Recharge Operator SP Keys')
            ->assertSee('Airtel Prepaid')
            ->assertSee('116')
            ->assertSee('Airtel Digital TV');
    }

    public function test_filters_and_clear(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Developer->value);

        Livewire::actingAs($user)
            ->test(RechargeOperators::class)
            ->set('typeFilter', 'dth')
            ->assertSee('Dish TV')
            ->assertDontSee('Jio Prepaid')
            ->set('search', 'Tata')
            ->assertSee('Tata Sky')
            ->call('clearFilters')
            ->assertSet('typeFilter', '')
            ->assertSet('search', '')
            ->assertSee('Jio Prepaid');
    }

    public function test_inactive_status_for_user_config(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Developer->value);

        UserOperatorCommission::query()->create([
            'user_id' => $user->id,
            'operator_type' => 'mobile',
            'operator_sp_key' => 116,
            'commission_percentage' => 2.5,
            'status' => false,
        ]);

        Livewire::actingAs($user)
            ->test(RechargeOperators::class)
            ->assertSee('Inactive');
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/user/recharge-operators')->assertRedirect();
    }
}
