<?php

namespace Tests\Feature\Filament;

use App\Enums\Role;
use App\Filament\Resources\ApiEndpoints\ApiEndpointResource;
use App\Filament\Resources\ApiVersions\ApiVersionResource;
use App\Models\User;
use Database\Seeders\CmsFoundationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CmsFoundationSeeder::class);
    }

    public function test_admin_login_page_is_available(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_user_login_page_is_available(): void
    {
        $this->get('/user/login')->assertOk();
    }

    public function test_super_admin_can_access_admin_panel(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SuperAdmin->value);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();
    }

    public function test_developer_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Developer->value);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_developer_can_access_user_panel(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Developer->value);

        $this->actingAs($user)
            ->get('/user')
            ->assertOk();
    }

    public function test_super_admin_cannot_access_user_panel(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SuperAdmin->value);

        $this->actingAs($user)
            ->get('/user')
            ->assertForbidden();
    }

    public function test_developer_user_panel_shows_dashboard_and_api_docs(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Developer->value);

        $this->actingAs($user)
            ->get('/user')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('API Docs');
    }

    public function test_cms_resources_are_registered(): void
    {
        $this->assertSame('Versions', ApiVersionResource::getNavigationLabel());
        $this->assertSame('Endpoints', ApiEndpointResource::getNavigationLabel());
        $this->assertSame('Documentation CMS', ApiEndpointResource::getNavigationGroup());
    }
}
