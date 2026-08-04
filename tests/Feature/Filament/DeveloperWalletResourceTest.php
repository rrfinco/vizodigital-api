<?php

namespace Tests\Feature\Filament;

use App\Enums\Role;
use App\Filament\Resources\DeveloperWallets\Pages\ListDeveloperWallets;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeveloperWalletResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_view_developer_wallets_list(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        $developer = User::factory()->create([
            'name' => 'Earn Dev',
            'company_name' => 'Earn Co',
            'wallet_balance' => 500.0000,
            'earning_balance' => 125.5000,
        ]);
        $developer->assignRole(Role::Developer->value);

        $this->actingAs($admin)
            ->get('/admin/developer-wallets')
            ->assertOk()
            ->assertSee('Developer wallets');

        Livewire::actingAs($admin)
            ->test(ListDeveloperWallets::class)
            ->assertCanSeeTableRecords([$developer])
            ->assertSee('Earn Dev')
            ->assertSee('125.50');
    }

    public function test_developer_cannot_access_admin_wallets_page(): void
    {
        $developer = User::factory()->create();
        $developer->assignRole(Role::Developer->value);

        $this->actingAs($developer)
            ->get('/admin/developer-wallets')
            ->assertForbidden();
    }

    public function test_staff_users_are_not_listed(): void
    {
        $admin = User::factory()->create(['name' => 'Staff Admin']);
        $admin->assignRole(Role::Admin->value);

        $developer = User::factory()->create([
            'name' => 'Only Dev',
            'earning_balance' => 10,
        ]);
        $developer->assignRole(Role::Developer->value);

        Livewire::actingAs($admin)
            ->test(ListDeveloperWallets::class)
            ->assertCanSeeTableRecords([$developer])
            ->assertCanNotSeeTableRecords([$admin]);
    }
}
