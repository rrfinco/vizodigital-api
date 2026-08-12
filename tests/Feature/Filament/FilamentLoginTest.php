<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolePermissionSeeder;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdminUserSeeder::class);
    }

    public function test_admin_can_login_to_admin_panel(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Login::class)
            ->set('data.email', 'admin@portal.test')
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect(Filament::getPanel('admin')->getUrl());

        $this->assertAuthenticatedAs(
            User::query()->where('email', 'admin@portal.test')->first()
        );
    }

    public function test_user_cannot_login_to_admin_panel(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Login::class)
            ->set('data.email', 'user@portal.test')
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasErrors(['data.email']);

        $this->assertGuest();
    }

    public function test_user_can_login_to_user_panel(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('user'));

        Livewire::test(Login::class)
            ->set('data.email', 'user@portal.test')
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect(Filament::getPanel('user')->getUrl());

        $this->assertAuthenticatedAs(
            User::query()->where('email', 'user@portal.test')->first()
        );
    }

    public function test_admin_cannot_login_to_user_panel(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('user'));

        Livewire::test(Login::class)
            ->set('data.email', 'admin@portal.test')
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasErrors(['data.email']);

        $this->assertGuest();
    }

    public function test_user_panel_password_reset_request_page_is_available(): void
    {
        $this->get('/user/password-reset/request')
            ->assertOk();
    }

    public function test_invalid_password_fails_on_admin_panel(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Login::class)
            ->set('data.email', 'admin@portal.test')
            ->set('data.password', 'wrong-password')
            ->call('authenticate')
            ->assertHasErrors(['data.email']);

        $this->assertGuest();
    }
}
