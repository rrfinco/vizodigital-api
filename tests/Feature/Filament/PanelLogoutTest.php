<?php

namespace Tests\Feature\Filament;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelLogoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_logout_via_filament_logout_route(): void
    {
        $user = User::factory()->create([
            'email' => 'admin-logout@portal.test',
        ]);
        $user->assignRole(Role::SuperAdmin->value);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('admin/logout', false)
            ->assertSee('Log out', false);

        $this->actingAs($user)
            ->post('/admin/logout')
            ->assertRedirect();

        $this->assertGuest();

        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_user_can_logout_via_filament_logout_route(): void
    {
        $user = User::factory()->create([
            'email' => 'user-logout@portal.test',
        ]);
        $user->assignRole(Role::Developer->value);

        $this->actingAs($user)
            ->get('/user')
            ->assertOk()
            ->assertSee('user/logout', false)
            ->assertSee('Log out', false);

        $this->actingAs($user)
            ->post('/user/logout')
            ->assertRedirect();

        $this->assertGuest();

        $this->get('/user')
            ->assertRedirect('/user/login');
    }

    public function test_portal_web_logout_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Developer->value);

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('landing'));

        $this->assertGuest();
    }

    public function test_admin_dashboard_renders_logout_as_post_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SuperAdmin->value);

        $html = $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Log out', false)
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<form[^>]+action="(?:https?:\/\/[^"]+)?\/admin\/logout"[^>]*method="post"/i',
            $html,
        );
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('action="/admin/logout"', $html);
    }

    public function test_user_dashboard_renders_logout_as_post_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Developer->value);

        $html = $this->actingAs($user)
            ->get('/user')
            ->assertOk()
            ->assertSee('Log out', false)
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<form[^>]+action="(?:https?:\/\/[^"]+)?\/user\/logout"[^>]*method="post"/i',
            $html,
        );
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('action="/user/logout"', $html);
    }
}
