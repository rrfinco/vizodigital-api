<?php

namespace Tests\Feature\Docs;

use App\Actions\Documentation\PublishEndpoint;
use App\Actions\Documentation\UnpublishEndpoint;
use App\Enums\HttpMethod;
use App\Enums\PublishStatus;
use App\Enums\Role;
use App\Models\ApiCategory;
use App\Models\ApiEndpoint;
use App\Models\ApiGroup;
use App\Models\ApiVersion;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\CmsFoundationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishPreviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $editor;

    private User $developer;

    private ApiEndpoint $endpoint;

    private ApiVersion $version;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CmsFoundationSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::SuperAdmin->value);

        $this->editor = User::factory()->create();
        $this->editor->assignRole(Role::Editor->value);

        $this->developer = User::factory()->create();
        $this->developer->assignRole(Role::Developer->value);

        $this->version = ApiVersion::query()->where('slug', 'v1')->firstOrFail();
        $this->version->update(['status' => PublishStatus::Published]);

        $category = ApiCategory::query()->create([
            'api_version_id' => $this->version->id,
            'name' => 'Core',
            'slug' => 'core',
            'status' => PublishStatus::Published,
            'sort_order' => 1,
        ]);

        $group = ApiGroup::query()->create([
            'api_category_id' => $category->id,
            'name' => 'Auth',
            'slug' => 'auth',
            'status' => PublishStatus::Published,
            'sort_order' => 1,
        ]);

        $this->endpoint = ApiEndpoint::query()->create([
            'api_group_id' => $group->id,
            'api_version_id' => $this->version->id,
            'name' => 'Get Token',
            'slug' => 'get-token',
            'method' => HttpMethod::Post,
            'path' => '/v1/auth/token',
            'summary' => 'Issue an API token',
            'status' => PublishStatus::Draft,
            'sort_order' => 1,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
    }

    public function test_draft_endpoint_is_not_publicly_visible(): void
    {
        $this->get(route('docs.endpoints.show', [
            'version' => 'v1',
            'endpoint' => 'get-token',
        ]))->assertNotFound();
    }

    public function test_published_endpoint_is_publicly_visible(): void
    {
        $this->actingAs($this->admin);
        app(PublishEndpoint::class)($this->endpoint);

        $this->get(route('docs.endpoints.show', [
            'version' => 'v1',
            'endpoint' => 'get-token',
        ]))
            ->assertOk()
            ->assertSee('Get Token')
            ->assertSee('/v1/auth/token')
            ->assertDontSee('Preview mode');
    }

    public function test_staff_can_preview_draft_endpoint(): void
    {
        $this->actingAs($this->editor)
            ->get(route('docs.preview.endpoints.show', $this->endpoint))
            ->assertOk()
            ->assertSee('Preview mode')
            ->assertSee('Get Token')
            ->assertSee('draft');
    }

    public function test_developer_cannot_preview_endpoint(): void
    {
        $this->actingAs($this->developer)
            ->get(route('docs.preview.endpoints.show', $this->endpoint))
            ->assertForbidden();
    }

    public function test_guest_cannot_preview_endpoint(): void
    {
        $this->get(route('docs.preview.endpoints.show', $this->endpoint))
            ->assertRedirect(route('login'));
    }

    public function test_publish_and_unpublish_write_audit_logs(): void
    {
        $this->actingAs($this->admin);

        app(PublishEndpoint::class)($this->endpoint);

        $this->assertTrue($this->endpoint->fresh()->isPublished());
        $this->assertNotNull($this->endpoint->fresh()->published_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'endpoint.published',
            'auditable_type' => ApiEndpoint::class,
            'auditable_id' => $this->endpoint->id,
            'user_id' => $this->admin->id,
        ]);

        app(UnpublishEndpoint::class)($this->endpoint->fresh());

        $this->assertTrue($this->endpoint->fresh()->isDraft());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'endpoint.unpublished',
            'auditable_id' => $this->endpoint->id,
        ]);
        $this->assertSame(2, AuditLog::query()->count());
    }

    public function test_editor_cannot_publish_endpoint(): void
    {
        $this->actingAs($this->editor);

        $this->expectException(AuthorizationException::class);

        app(PublishEndpoint::class)($this->endpoint);
    }
}
