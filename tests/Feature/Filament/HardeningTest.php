<?php

namespace Tests\Feature\Filament;

use App\Enums\Role;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\ApiEndpoints\ApiEndpointResource;
use App\Filament\Resources\ApiEnvironments\ApiEnvironmentResource;
use App\Filament\Resources\ApiVersions\ApiVersionResource;
use App\Filament\Resources\NavigationItems\NavigationItemResource;
use App\Models\User;
use App\Services\Portal\PortalSettings;
use Database\Seeders\CmsFoundationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CmsFoundationSeeder::class);
    }

    public function test_viewer_can_list_but_not_create_endpoints(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole(Role::Viewer->value);

        $this->actingAs($viewer);

        $this->assertTrue(ApiEndpointResource::canViewAny());
        $this->assertFalse(ApiEndpointResource::canCreate());
        $this->assertFalse(ApiEndpointResource::canPublish());
    }

    public function test_editor_cannot_publish_or_manage_navigation(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole(Role::Editor->value);

        $this->actingAs($editor);

        $this->assertTrue(ApiEndpointResource::canCreate());
        $this->assertFalse(ApiEndpointResource::canPublish());
        $this->assertFalse(ApiEndpointResource::canDeleteAny());
        $this->assertFalse(NavigationItemResource::canCreate());
        $this->assertTrue(ApiVersionResource::canCreate());
        $this->assertTrue(ApiEnvironmentResource::canCreate());
        $this->assertFalse(ManageSettings::canAccess());
    }

    public function test_admin_can_manage_settings_and_navigation(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        $this->actingAs($admin);

        $this->assertTrue(ManageSettings::canAccess());
        $this->assertTrue(NavigationItemResource::canCreate());
        $this->assertTrue(ApiEndpointResource::canPublish());

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->fillForm([
                'name' => 'Acme Docs',
                'tagline' => 'Build faster',
                'logo_text' => 'Acme',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(PortalSettings::class);
        $settings->forgetCache();

        $this->assertSame('Acme Docs', $settings->name());
        $this->assertSame('Build faster', $settings->tagline());
        $this->assertSame('Acme', $settings->logoText());
    }

    public function test_editor_cannot_open_settings_page(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole(Role::Editor->value);

        $this->actingAs($editor)
            ->get(ManageSettings::getUrl())
            ->assertForbidden();
    }

    public function test_explorer_empty_state_copy(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);
        $this->actingAs($admin);

        // Ensure v1 is published so explorer resolves, but no categories exist.
        \App\Models\ApiVersion::query()->where('slug', 'v1')->update([
            'status' => \App\Enums\PublishStatus::Published,
        ]);

        $this->get(route('docs.explorer', ['version' => 'v1']))
            ->assertOk()
            ->assertSee('No published APIs yet');
    }
}
