<?php

namespace Tests\Feature\Cms;

use App\Enums\EnvironmentSlug;
use App\Enums\HttpMethod;
use App\Enums\PublishStatus;
use App\Enums\SectionKey;
use App\Models\ApiCategory;
use App\Models\ApiEndpoint;
use App\Models\ApiEnvironment;
use App\Models\ApiGroup;
use App\Models\ApiVersion;
use App\Models\SectionDefinition;
use App\Repositories\Contracts\DocumentationRepositoryInterface;
use App\Repositories\Contracts\EnvironmentRepositoryInterface;
use Database\Seeders\CmsFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CmsFoundationSeeder::class);
    }

    public function test_foundation_seed_creates_environments_and_version(): void
    {
        $this->assertDatabaseHas('api_environments', ['slug' => EnvironmentSlug::Uat->value]);
        $this->assertDatabaseHas('api_environments', ['slug' => EnvironmentSlug::Production->value]);
        $this->assertDatabaseHas('api_versions', ['slug' => 'v1', 'is_default' => 1]);

        $this->assertSame(
            count(SectionKey::cases()),
            SectionDefinition::query()->count()
        );
    }

    public function test_creating_endpoint_auto_creates_section_layout(): void
    {
        $version = ApiVersion::query()->where('slug', 'v1')->firstOrFail();

        $category = ApiCategory::query()->create([
            'api_version_id' => $version->id,
            'name' => 'Demo Category',
            'slug' => 'demo-category',
            'status' => PublishStatus::Draft,
        ]);

        $group = ApiGroup::query()->create([
            'api_category_id' => $category->id,
            'name' => 'Demo Group',
            'slug' => 'demo-group',
            'status' => PublishStatus::Draft,
        ]);

        $endpoint = ApiEndpoint::query()->create([
            'api_group_id' => $group->id,
            'api_version_id' => $version->id,
            'name' => 'List Items',
            'slug' => 'list-items',
            'method' => HttpMethod::Get,
            'path' => '/v1/items',
            'status' => PublishStatus::Draft,
        ]);

        $this->assertSame(count(SectionKey::defaultLayout()), $endpoint->sections()->count());
        $this->assertTrue($endpoint->sections()->where('section_key', SectionKey::Headers->value)->where('enabled', true)->exists());
        $this->assertTrue($endpoint->sections()->where('section_key', SectionKey::TryApi->value)->where('enabled', false)->exists());
    }

    public function test_environment_repository_returns_default_uat(): void
    {
        $env = app(EnvironmentRepositoryInterface::class)->default();

        $this->assertNotNull($env);
        $this->assertSame(EnvironmentSlug::Uat, $env->slug);
    }

    public function test_published_category_tree_is_empty_without_published_content(): void
    {
        $tree = app(DocumentationRepositoryInterface::class)->publishedCategoryTree('v1');

        $this->assertCount(0, $tree);
    }

    public function test_no_hardcoded_api_endpoints_exist_after_foundation_seed(): void
    {
        $this->assertSame(0, ApiEndpoint::query()->count());
        $this->assertSame(0, ApiCategory::query()->count());
    }
}
