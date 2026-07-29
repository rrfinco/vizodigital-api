<?php

namespace Tests\Feature\Filament;

use App\Enums\Role;
use App\Filament\Resources\ChangelogEntries\ChangelogEntryResource;
use App\Filament\Resources\DocumentationPages\DocumentationPageResource;
use App\Filament\Resources\Faqs\FaqResource;
use App\Filament\Resources\MediaAssets\MediaAssetResource;
use App\Models\User;
use Database\Seeders\CmsFoundationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsPagesPackAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CmsFoundationSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::SuperAdmin->value);
    }

    public function test_admin_can_open_cms_pack_resources(): void
    {
        $this->actingAs($this->admin);

        $this->get(DocumentationPageResource::getUrl('index'))->assertOk();
        $this->get(FaqResource::getUrl('index'))->assertOk();
        $this->get(ChangelogEntryResource::getUrl('index'))->assertOk();
        $this->get(MediaAssetResource::getUrl('index'))->assertOk();
    }
}
