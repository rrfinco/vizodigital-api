<?php

namespace Tests\Feature\Whitelabel;

use App\Enums\Role;
use App\Enums\WhitelabelStatus;
use App\Models\User;
use App\Models\Whitelabel;
use App\Models\WhitelabelDomain;
use App\Services\Portal\PortalSettings;
use App\Services\Whitelabel\WhitelabelContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WhitelabelDomainResolveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Mail::fake();
    }

    public function test_host_middleware_resolves_whitelabel_context(): void
    {
        $wl = Whitelabel::factory()->create([
            'name' => 'Acme APIs',
            'brand_name' => 'Acme Brand',
            'primary_color' => '#0F766E',
        ]);

        WhitelabelDomain::query()->create([
            'whitelabel_id' => $wl->id,
            'host' => 'api.acme.test',
            'is_primary' => true,
        ]);

        $this->get('http://api.acme.test/register')
            ->assertOk();

        $context = app(WhitelabelContext::class);
        $this->assertNotNull($context->whitelabel());
        $this->assertSame($wl->id, $context->id());
        $this->assertSame('Acme Brand', app(PortalSettings::class)->name());
    }

    public function test_unknown_host_keeps_platform_branding(): void
    {
        $this->get('http://localhost/register')->assertOk();

        $this->assertNull(app(WhitelabelContext::class)->whitelabel());
        $this->assertSame(
            (string) config('portal.name'),
            app(PortalSettings::class)->name()
        );
    }

    public function test_registration_on_whitelabel_host_assigns_whitelabel_id(): void
    {
        $wl = Whitelabel::factory()->create(['brand_name' => 'Partner Co']);
        WhitelabelDomain::query()->create([
            'whitelabel_id' => $wl->id,
            'host' => 'partner.co.test',
            'is_primary' => true,
        ]);

        $response = $this->post('http://partner.co.test/register', [
            'name' => 'WL Dev',
            'email' => 'dev@partner.co.test',
            'company_name' => 'Dev Co',
            'phone' => '9876543210',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('register.thanks'));

        $user = User::query()->where('email', 'dev@partner.co.test')->first();
        $this->assertNotNull($user);
        $this->assertSame($wl->id, (int) $user->whitelabel_id);
        $this->assertTrue($user->hasRole(Role::Developer->value));
    }

    public function test_registration_on_platform_host_has_null_whitelabel(): void
    {
        $response = $this->post('/register', [
            'name' => 'B2C Dev',
            'email' => 'b2c@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('register.thanks'));

        $user = User::query()->where('email', 'b2c@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->whitelabel_id);
    }

    public function test_suspended_whitelabel_blocks_registration(): void
    {
        $wl = Whitelabel::factory()->suspended()->create();
        WhitelabelDomain::query()->create([
            'whitelabel_id' => $wl->id,
            'host' => 'down.partner.test',
            'is_primary' => true,
        ]);

        $response = $this->from('http://down.partner.test/register')
            ->post('http://down.partner.test/register', [
                'name' => 'Blocked Dev',
                'email' => 'blocked@partner.test',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

        $response->assertRedirect('http://down.partner.test/register');
        $this->assertDatabaseMissing('users', ['email' => 'blocked@partner.test']);
    }

    public function test_host_normalization_is_case_insensitive(): void
    {
        $wl = Whitelabel::factory()->create();
        WhitelabelDomain::query()->create([
            'whitelabel_id' => $wl->id,
            'host' => 'Brand.Example.COM',
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('whitelabel_domains', [
            'whitelabel_id' => $wl->id,
            'host' => 'brand.example.com',
        ]);

        app(WhitelabelContext::class)->resolveFromHost('BRAND.EXAMPLE.COM');
        $this->assertSame($wl->id, app(WhitelabelContext::class)->id());
    }

    public function test_admin_panel_blocked_on_whitelabel_host(): void
    {
        $wl = Whitelabel::factory()->create();
        WhitelabelDomain::query()->create([
            'whitelabel_id' => $wl->id,
            'host' => 'wl.admin-block.test',
            'is_primary' => true,
        ]);

        $this->get('http://wl.admin-block.test/admin/login')->assertNotFound();
    }

    public function test_wrong_role_on_admin_is_logged_out_not_403(): void
    {
        $wl = Whitelabel::factory()->create();
        $partner = User::factory()->forWhitelabel($wl->id)->create([
            'onboarding_status' => \App\Enums\OnboardingStatus::Approved,
        ]);
        $partner->assignRole(Role::Whitelabel->value);

        $this->actingAs($partner)
            ->get('/admin')
            ->assertRedirect();

        $this->assertGuest();
    }
}
