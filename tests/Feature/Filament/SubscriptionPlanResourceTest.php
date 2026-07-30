<?php

namespace Tests\Feature\Filament;

use App\Enums\HttpMethod;
use App\Enums\PublishStatus;
use App\Enums\Role;
use App\Filament\Resources\SubscriptionPlans\SubscriptionPlanResource;
use App\Models\ApiCategory;
use App\Models\ApiEndpoint;
use App\Models\ApiGroup;
use App\Models\ApiVersion;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\CmsFoundationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlanResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CmsFoundationSeeder::class);
    }

    public function test_admin_can_access_subscription_plans_resource(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        $this->actingAs($admin)
            ->get(SubscriptionPlanResource::getUrl('index'))
            ->assertOk();
    }

    public function test_subscription_plan_can_attach_services(): void
    {
        $version = ApiVersion::create([
            'name' => 'Billing Test Version',
            'slug' => 'billing-test-version',
            'status' => PublishStatus::Published,
            'sort_order' => 1,
        ]);

        $category = ApiCategory::create([
            'api_version_id' => $version->id,
            'name' => 'Wallet',
            'slug' => 'wallet',
            'status' => PublishStatus::Published,
            'show_in_sidebar' => true,
            'sort_order' => 1,
        ]);

        $group = ApiGroup::create([
            'api_category_id' => $category->id,
            'name' => 'Wallet Services',
            'slug' => 'wallet-services',
            'status' => PublishStatus::Published,
            'sort_order' => 1,
        ]);

        $endpoint = ApiEndpoint::create([
            'api_group_id' => $group->id,
            'api_version_id' => $version->id,
            'name' => 'Add Funds',
            'slug' => 'add-funds',
            'method' => HttpMethod::Post,
            'path' => '/wallet/add-funds',
            'status' => PublishStatus::Published,
            'sort_order' => 1,
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Starter Plan',
            'slug' => 'starter-plan',
            'price' => 499,
            'duration_days' => 30,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $plan->endpoints()->sync([$endpoint->id]);

        $this->assertCount(1, $plan->fresh()->endpoints);
        $this->assertTrue($endpoint->fresh()->subscriptionPlans->contains('id', $plan->id));
    }
}
