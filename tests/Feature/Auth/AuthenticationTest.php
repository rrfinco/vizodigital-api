<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role as RoleModel;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_users_can_authenticate_via_web(): void
    {
        $user = User::factory()->create([
            'email' => 'editor@portal.test',
            'password' => 'password',
        ]);
        $user->assignRole(Role::Editor->value);

        $this->post(route('login.store'), [
            'email' => 'editor@portal.test',
            'password' => 'password',
        ])->assertRedirect(route('docs.overview'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_request_sanctum_token(): void
    {
        $user = User::factory()->create([
            'email' => 'api@portal.test',
            'password' => 'password',
        ]);
        $user->assignRole(Role::Developer->value);

        $response = $this->postJson('/api/v1/auth/token', [
            'email' => 'api@portal.test',
            'password' => 'password',
            'device_name' => 'phpunit',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'email', 'roles']]);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_authenticated_user_can_view_profile(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Viewer->value);

        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee($user->email);
    }

    public function test_super_admin_bypasses_gates(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SuperAdmin->value);

        $this->assertTrue($user->can('docs.publish'));
        $this->assertTrue($user->can('users.manage'));
    }

    public function test_editor_cannot_manage_users(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Editor->value);

        $this->assertTrue($user->can('docs.create'));
        $this->assertFalse($user->can('users.manage'));
    }

    public function test_sanctum_me_endpoint_requires_token(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();

        $user = User::factory()->create();
        RoleModel::findOrCreate(Role::Developer->value, 'web');
        $user->assignRole(Role::Developer->value);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('email', $user->email);
    }
}
