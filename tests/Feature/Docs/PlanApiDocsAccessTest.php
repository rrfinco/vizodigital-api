<?php

namespace Tests\Feature\Docs;

use App\Enums\HttpMethod;
use App\Enums\PublishStatus;
use App\Enums\Role;
use App\Models\ApiCategory;
use App\Models\ApiEndpoint;
use App\Models\ApiGroup;
use App\Models\ApiVersion;
use App\Models\User;
use App\Models\UserPlanApiAccess;
use App\Services\PlanApi\PlanApiService;
use Database\Seeders\CmsFoundationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanApiDocsAccessTest extends TestCase
{
    use RefreshDatabase;

    private ApiVersion $version;

    private ApiEndpoint $publicEndpoint;

    private ApiEndpoint $gatedEndpoint;

    private User $developer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CmsFoundationSeeder::class);

        $this->version = ApiVersion::query()->where('slug', 'v1')->firstOrFail();
        $this->version->update(['status' => PublishStatus::Published]);

        $category = ApiCategory::query()->create([
            'api_version_id' => $this->version->id,
            'name' => 'Core',
            'slug' => 'core',
            'status' => PublishStatus::Published,
            'sort_order' => 1,
            'show_in_sidebar' => true,
        ]);

        $group = ApiGroup::query()->create([
            'api_category_id' => $category->id,
            'name' => 'Auth',
            'slug' => 'auth',
            'status' => PublishStatus::Published,
            'sort_order' => 1,
        ]);

        $this->publicEndpoint = ApiEndpoint::query()->create([
            'api_group_id' => $group->id,
            'api_version_id' => $this->version->id,
            'name' => 'Get Token',
            'slug' => 'get-token',
            'method' => HttpMethod::Post,
            'path' => '/v1/auth/token',
            'summary' => 'Issue an API token',
            'status' => PublishStatus::Published,
            'published_at' => now(),
            'sort_order' => 1,
        ]);

        $planCategory = ApiCategory::query()->create([
            'api_version_id' => $this->version->id,
            'name' => 'Plan & Operator',
            'slug' => 'plan-apis',
            'status' => PublishStatus::Published,
            'sort_order' => 2,
            'show_in_sidebar' => true,
        ]);

        $planGroup = ApiGroup::query()->create([
            'api_category_id' => $planCategory->id,
            'name' => 'Operator & Plans',
            'slug' => 'verification',
            'status' => PublishStatus::Published,
            'sort_order' => 1,
        ]);

        $this->gatedEndpoint = ApiEndpoint::query()->create([
            'api_group_id' => $planGroup->id,
            'api_version_id' => $this->version->id,
            'name' => 'Mobile Operator Find',
            'slug' => 'operator-fetch',
            'method' => HttpMethod::Post,
            'path' => '/api/v1/plan/operator-fetch',
            'summary' => 'Detect operator',
            'status' => PublishStatus::Published,
            'published_at' => now(),
            'access_service_key' => PlanApiService::SERVICE_OPERATOR_FETCH,
            'sort_order' => 1,
        ]);

        $this->developer = User::factory()->create();
        $this->developer->assignRole(Role::Developer->value);
    }

    public function test_guest_sees_public_endpoint_but_not_gated_plan_api(): void
    {
        $this->get(route('docs.endpoints.show', [
            'version' => 'v1',
            'endpoint' => $this->publicEndpoint->slug,
        ]))->assertOk();

        $this->get(route('docs.endpoints.show', [
            'version' => 'v1',
            'endpoint' => $this->gatedEndpoint->slug,
        ]))->assertNotFound();

        $this->get(route('docs.overview'))
            ->assertOk()
            ->assertSee('Get Token')
            ->assertDontSee('Mobile Operator Find');
    }

    public function test_developer_without_access_cannot_see_gated_docs(): void
    {
        $this->actingAs($this->developer)
            ->get(route('docs.endpoints.show', [
                'version' => 'v1',
                'endpoint' => $this->gatedEndpoint->slug,
            ]))
            ->assertNotFound();

        $this->actingAs($this->developer)
            ->get(route('docs.overview'))
            ->assertOk()
            ->assertDontSee('Mobile Operator Find');
    }

    public function test_developer_with_active_access_sees_gated_docs(): void
    {
        UserPlanApiAccess::query()->create([
            'user_id' => $this->developer->id,
            'service' => PlanApiService::SERVICE_OPERATOR_FETCH,
            'status' => true,
            'per_call_fee' => 0.10,
        ]);

        $this->actingAs($this->developer)
            ->get(route('docs.endpoints.show', [
                'version' => 'v1',
                'endpoint' => $this->gatedEndpoint->slug,
            ]))
            ->assertOk()
            ->assertSee('Mobile Operator Find');

        $this->actingAs($this->developer)
            ->get(route('docs.overview'))
            ->assertOk()
            ->assertSee('Mobile Operator Find');
    }

    public function test_inactive_access_hides_gated_docs(): void
    {
        UserPlanApiAccess::query()->create([
            'user_id' => $this->developer->id,
            'service' => PlanApiService::SERVICE_OPERATOR_FETCH,
            'status' => false,
            'per_call_fee' => 0.10,
        ]);

        $this->actingAs($this->developer)
            ->get(route('docs.endpoints.show', [
                'version' => 'v1',
                'endpoint' => $this->gatedEndpoint->slug,
            ]))
            ->assertNotFound();
    }
}
