<?php

namespace Tests\Feature\Docs;

use App\Actions\Documentation\PublishEndpoint;
use App\Enums\HttpMethod;
use App\Enums\NavigationTargetType;
use App\Enums\PublishStatus;
use App\Enums\Role;
use App\Enums\SnippetLanguage;
use App\Models\ApiCategory;
use App\Models\ApiEndpoint;
use App\Models\ApiEnvironment;
use App\Models\ApiGroup;
use App\Models\ApiVersion;
use App\Models\NavigationItem;
use App\Models\User;
use Database\Seeders\CmsFoundationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalNavigationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ApiEndpoint $endpoint;

    private ApiVersion $version;

    private ApiEnvironment $uat;

    private ApiEnvironment $production;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CmsFoundationSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::SuperAdmin->value);

        $this->version = ApiVersion::query()->where('slug', 'v1')->firstOrFail();
        $this->version->update(['status' => PublishStatus::Published]);

        $this->uat = ApiEnvironment::query()->where('slug', 'uat')->firstOrFail();
        $this->production = ApiEnvironment::query()->where('slug', 'production')->firstOrFail();

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

        $this->endpoint->examples()->create([
            'api_environment_id' => $this->uat->id,
            'title' => 'UAT sample',
            'request' => ['env' => 'uat'],
            'response' => ['ok' => true],
            'response_status' => 200,
            'sort_order' => 1,
        ]);

        $this->endpoint->examples()->create([
            'api_environment_id' => $this->production->id,
            'title' => 'Production sample',
            'request' => ['env' => 'production'],
            'response' => ['ok' => true],
            'response_status' => 200,
            'sort_order' => 1,
        ]);

        $this->endpoint->codeSamples()->create([
            'api_environment_id' => $this->uat->id,
            'language' => SnippetLanguage::Curl,
            'code' => 'curl https://uat-api.example.com/v1/auth/token',
            'sort_order' => 1,
        ]);

        $this->endpoint->codeSamples()->create([
            'api_environment_id' => $this->production->id,
            'language' => SnippetLanguage::Curl,
            'code' => 'curl https://api.example.com/v1/auth/token',
            'sort_order' => 1,
        ]);

        $this->actingAs($this->admin);
        app(PublishEndpoint::class)($this->endpoint);
    }

    public function test_docs_sidebar_renders_cms_navigation_items(): void
    {
        $this->get(route('docs.overview'))
            ->assertOk()
            ->assertSee('Overview')
            ->assertSee('API Explorer')
            ->assertDontSee('Sidebar will load from CMS navigation');
    }

    public function test_docs_sidebar_keeps_reference_links_out_of_getting_started(): void
    {
        NavigationItem::query()->updateOrCreate(
            [
                'api_version_id' => $this->version->id,
                'label' => 'API Explorer',
                'parent_id' => null,
            ],
            [
                'target_type' => NavigationTargetType::Explorer,
                'route_name' => null,
                'url' => null,
                'is_visible' => true,
                'sort_order' => 2,
            ]
        );

        NavigationItem::query()->updateOrCreate(
            [
                'api_version_id' => $this->version->id,
                'label' => 'FAQs',
                'parent_id' => null,
            ],
            [
                'target_type' => NavigationTargetType::Url,
                'route_name' => 'docs.faqs.index',
                'url' => null,
                'is_visible' => true,
                'sort_order' => 3,
            ]
        );

        $html = $this->get(route('docs.overview'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '>API Explorer</span>'));
        $this->assertSame(1, substr_count($html, '>FAQs</span>'));
        $this->assertSame(1, substr_count($html, '>Changelog</span>'));
        $this->assertSame(1, substr_count($html, '>SDK</span>'));
    }

    public function test_docs_sidebar_has_single_overview_and_only_current_page_active(): void
    {
        $this->actingAs($this->admin);
        app(PublishEndpoint::class)($this->endpoint);

        $html = $this->get(route('docs.endpoints.show', [
            'version' => 'v1',
            'endpoint' => 'get-token',
        ]))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '>Overview</span>'));
        $this->assertMatchesRegularExpression(
            '/bg-sky-50[^"]*"[^>]*>\s*<span class="truncate">Get Token<\/span>/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/bg-sky-50[^"]*"[^>]*>\s*<span class="truncate">Overview<\/span>/',
            $html
        );
    }

    public function test_environment_switcher_rebinds_examples_and_base_url(): void
    {
        $uatResponse = $this->get(route('docs.endpoints.show', [
            'version' => 'v1',
            'endpoint' => 'get-token',
            'env' => 'uat',
        ]));

        $uatResponse->assertOk()
            ->assertSee('curl https://uat-api.example.com/v1/auth/token')
            ->assertDontSee('curl https://api.example.com/v1/auth/token')
            ->assertSee(config('portal.environments.uat.base_url'));

        $prodResponse = $this->get(route('docs.endpoints.show', [
            'version' => 'v1',
            'endpoint' => 'get-token',
            'env' => 'production',
        ]));

        $prodResponse->assertOk()
            ->assertSee('curl https://api.example.com/v1/auth/token')
            ->assertDontSee('curl https://uat-api.example.com/v1/auth/token')
            ->assertSee(config('portal.environments.production.base_url'));
    }

    public function test_environment_persists_in_session_across_requests(): void
    {
        $this->get(route('docs.endpoints.show', [
            'version' => 'v1',
            'endpoint' => 'get-token',
            'env' => 'production',
        ]))->assertOk();

        $this->get(route('docs.endpoints.show', [
            'version' => 'v1',
            'endpoint' => 'get-token',
        ]))
            ->assertOk()
            ->assertSee('curl https://api.example.com/v1/auth/token')
            ->assertDontSee('curl https://uat-api.example.com/v1/auth/token');
    }

    public function test_version_switcher_and_explorer_list_published_endpoints(): void
    {
        $this->get(route('docs.explorer', ['version' => 'v1']))
            ->assertOk()
            ->assertSee('API Explorer')
            ->assertSee('Get Token')
            ->assertSee('Core');

        $this->get(route('docs.categories.show', [
            'version' => 'v1',
            'category' => 'core',
        ]))
            ->assertOk()
            ->assertSee('Core')
            ->assertSee('Get Token');

        $this->get(route('docs.groups.show', [
            'version' => 'v1',
            'group' => 'auth',
        ]))
            ->assertOk()
            ->assertSee('Get Token');
    }

    public function test_custom_navigation_item_appears_in_sidebar(): void
    {
        NavigationItem::query()->create([
            'api_version_id' => $this->version->id,
            'label' => 'Custom Guide',
            'target_type' => NavigationTargetType::Url,
            'url' => 'https://example.com/guide',
            'is_visible' => true,
            'sort_order' => 10,
        ]);

        $this->get(route('docs.overview'))
            ->assertOk()
            ->assertSee('Custom Guide')
            ->assertSee('https://example.com/guide');
    }

    public function test_version_is_attached_to_environments_by_seeder(): void
    {
        $this->assertTrue($this->version->environments()->where('slug', 'uat')->exists());
        $this->assertTrue($this->version->environments()->where('slug', 'production')->exists());
    }
}
