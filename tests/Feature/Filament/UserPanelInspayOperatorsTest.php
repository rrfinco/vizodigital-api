<?php

namespace Tests\Feature\Filament;

use App\Enums\Role;
use App\Filament\Pages\InspayOperators as AdminInspayOperators;
use App\Filament\User\Pages\InspayOperators as UserInspayOperators;
use App\Models\User;
use App\Models\UserBillOperatorCommission;
use App\Services\Inspay\InspayOperatorCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserPanelInspayOperatorsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_developer_can_access_inspay_operators_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Developer->value);

        $this->actingAs($user)
            ->get('/user/inspay-operators')
            ->assertOk()
            ->assertSee('Bill Payment Opcode');
    }

    public function test_catalog_loads_operators_and_categories(): void
    {
        $catalog = app(InspayOperatorCatalog::class);

        $this->assertGreaterThan(1000, $catalog->all()->count());
        $this->assertContains('Electricity Bill', $catalog->categories());
        $this->assertTrue(
            $catalog->search(category: 'Credit Card')->isNotEmpty()
        );
        $this->assertTrue(
            $catalog->search(query: 'Airtel')->isNotEmpty()
        );
    }

    public function test_filters_narrow_results(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Developer->value);

        Livewire::actingAs($user)
            ->test(UserInspayOperators::class)
            ->set('category', 'DTH Recharge')
            ->assertSee('DTH Recharge')
            ->set('search', 'Airtel')
            ->assertSee('Airtel');
    }

    public function test_clear_filters_resets_category_and_search(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Developer->value);

        Livewire::actingAs($user)
            ->test(UserInspayOperators::class)
            ->set('category', 'DTH Recharge')
            ->set('search', 'Airtel')
            ->assertSet('category', 'DTH Recharge')
            ->assertSet('search', 'Airtel')
            ->call('clearFilters')
            ->assertSet('category', '')
            ->assertSet('search', '')
            ->assertSet('filterVersion', 1);
    }

    public function test_clear_category_and_search_individually(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Developer->value);

        Livewire::actingAs($user)
            ->test(UserInspayOperators::class)
            ->set('category', 'Credit Card')
            ->set('search', 'ICICI')
            ->call('clearCategory')
            ->assertSet('category', '')
            ->assertSet('search', 'ICICI')
            ->call('clearSearch')
            ->assertSet('search', '');
    }

    public function test_category_chip_filter_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Developer->value);

        Livewire::actingAs($user)
            ->test(UserInspayOperators::class)
            ->call('selectCategory', 'Credit Card')
            ->assertSet('category', 'Credit Card')
            ->assertSee('Credit Card');
    }

    public function test_admin_can_access_inspay_operators_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        $this->actingAs($admin)
            ->get('/admin/inspay-operators')
            ->assertOk()
            ->assertSee('InsPay Operator Codes');
    }

    public function test_guest_is_redirected_from_admin_inspay_operators(): void
    {
        $this->get('/admin/inspay-operators')
            ->assertRedirect();
    }

    public function test_admin_livewire_page_loads(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        Livewire::actingAs($admin)
            ->test(AdminInspayOperators::class)
            ->assertSee('InsPay Operator Codes')
            ->call('selectCategory', 'Fastag')
            ->assertSet('category', 'Fastag');
    }

    public function test_admin_can_save_flat_and_percentage_commissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        $developer = User::factory()->create(['name' => 'Dev One']);
        $developer->assignRole(Role::Developer->value);

        $catalog = app(InspayOperatorCatalog::class);
        $first = $catalog->search(category: 'Credit Card')->first();
        $this->assertNotNull($first);
        $opcode = $first['code'];

        $component = Livewire::actingAs($admin)
            ->test(AdminInspayOperators::class)
            ->set('selectedUserId', $developer->id)
            ->set('category', 'Credit Card');

        $rows = $component->get('commissionRows');
        $this->assertArrayHasKey($opcode, $rows);

        $rows[$opcode] = [
            'commission_type' => 'flat',
            'commission_value' => '12.50',
            'status' => 'Active',
        ];

        $instance = $component->instance();
        $instance->commissionRows = $rows;
        $instance->saveCommissions();

        $row = UserBillOperatorCommission::query()
            ->where('user_id', $developer->id)
            ->where('opcode', $opcode)
            ->first();

        $this->assertNotNull($row);
        $this->assertEquals('flat', $row->commission_type);
        $this->assertEquals(12.50, (float) $row->commission_value);
        $this->assertTrue((bool) $row->status);

        $rows[$opcode] = [
            'commission_type' => 'percentage',
            'commission_value' => '2.25',
            'status' => 'Active',
        ];

        $instance->commissionRows = $rows;
        $instance->saveCommissions();

        $row->refresh();
        $this->assertEquals('percentage', $row->commission_type);
        $this->assertEquals(2.25, (float) $row->commission_value);
    }

    public function test_developer_sees_own_commission_on_opcode_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Developer->value);

        UserBillOperatorCommission::create([
            'user_id' => $user->id,
            'opcode' => 'ICIC',
            'commission_type' => 'percentage',
            'commission_value' => 3.50,
            'status' => true,
        ]);

        Livewire::actingAs($user)
            ->test(UserInspayOperators::class)
            ->set('search', 'ICIC')
            ->assertSee('3.50')
            ->assertSee('%');
    }
}
