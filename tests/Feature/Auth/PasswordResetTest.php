<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Forgot password');
    }

    public function test_login_screen_links_to_forgot_password(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Forgot password?')
            ->assertSee(route('password.request'), false);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset@portal.test',
        ]);
        $user->assignRole(Role::Developer->value);

        $this->post(route('password.email'), [
            'email' => 'reset@portal.test',
        ])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset@portal.test',
            'password' => Hash::make('old-password'),
        ]);
        $user->assignRole(Role::Developer->value);

        $this->post(route('password.email'), [
            'email' => 'reset@portal.test',
        ]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $response = $this->get(route('password.reset', [
                'token' => $notification->token,
                'email' => $user->email,
            ]));

            $response->assertOk()->assertSee('Reset password');

            $this->post(route('password.update'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertRedirect(route('login'));

            $this->assertTrue(Hash::check('new-password', $user->fresh()->password));

            return true;
        });
    }

    public function test_password_reset_fails_with_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@portal.test',
            'password' => Hash::make('old-password'),
        ]);

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }
}
