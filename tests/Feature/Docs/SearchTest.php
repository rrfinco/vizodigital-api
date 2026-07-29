<?php

namespace Tests\Feature\Docs;

use App\Actions\Documentation\PublishEndpoint;
use App\Actions\Documentation\UnpublishEndpoint;
use App\Enums\DocPageType;
use App\Enums\HttpMethod;
use App\Enums\PublishStatus;
use App\Enums\Role;
use App\Enums\SearchDocumentType;
use App\Models\ApiCategory;
use App\Models\ApiEndpoint;
use App\Models\ApiGroup;
use App\Models\ApiVersion;
use App\Models\DocumentationPage;
use App\Models\SearchIndex;
use App\Models\User;
use Database\Seeders\CmsFoundationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ApiEndpoint $endpoint;

    private ApiVersion $version;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CmsFoundationSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::SuperAdmin->value);

        $this->version = ApiVersion::query()->where('slug', 'v1')->firstOrFail();
        $this->version->update(['status' => PublishStatus::Published]);

        $category = ApiCategory::query()->create([
            'api_version_id' => $this->version->id,
            'name' => 'Core',
            'slug' => 'core',
            'description' => 'Core APIs',
            'status' => PublishStatus::Published,
            'sort_order' => 1,
            'show_in_sidebar' => true,
        ]);

        $group = ApiGroup::query()->create([
            'api_category_id' => $category->id,
            'name' => 'Auth',
            'slug' => 'auth',
            'description' => 'Authentication group',
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
            'summary' => 'Issue an API bearer token',
            'description_md' => 'Creates a **token** for clients.',
            'status' => PublishStatus::Draft,
            'sort_order' => 1,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        DocumentationPage::query()->create([
            'api_version_id' => $this->version->id,
            'type' => DocPageType::Authentication,
            'title' => 'Authentication Guide',
            'slug' => 'authentication',
            'body_md' => 'Use bearer tokens for every request.',
            'status' => PublishStatus::Published,
            'published_at' => now(),
            'sort_order' => 1,
        ]);
    }

    public function test_publishing_endpoint_indexes_it_for_search(): void
    {
        $this->actingAs($this->admin);
        app(PublishEndpoint::class)($this->endpoint);

        $this->assertDatabaseHas('search_index', [
            'searchable_type' => ApiEndpoint::class,
            'searchable_id' => $this->endpoint->id,
            'type' => SearchDocumentType::Endpoint->value,
            'title' => 'Get Token',
            'status' => PublishStatus::Published->value,
        ]);

        $this->getJson(route('docs.search', ['q' => 'token', 'version' => 'v1']))
            ->assertOk()
            ->assertJsonPath('query', 'token')
            ->assertJsonFragment([
                'title' => 'Get Token',
                'type' => 'endpoint',
            ]);
    }

    public function test_draft_endpoint_is_not_returned_in_search(): void
    {
        $this->getJson(route('docs.search', ['q' => 'Get Token', 'version' => 'v1']))
            ->assertOk()
            ->assertJsonMissing(['title' => 'Get Token']);
    }

    public function test_unpublishing_removes_endpoint_from_search_results(): void
    {
        $this->actingAs($this->admin);
        app(PublishEndpoint::class)($this->endpoint);
        app(UnpublishEndpoint::class)($this->endpoint->fresh());

        $this->assertDatabaseHas('search_index', [
            'searchable_id' => $this->endpoint->id,
            'status' => PublishStatus::Draft->value,
        ]);

        $this->getJson(route('docs.search', ['q' => 'Get Token', 'version' => 'v1']))
            ->assertOk()
            ->assertJsonMissing(['title' => 'Get Token']);
    }

    public function test_search_finds_published_pages_and_categories(): void
    {
        $this->getJson(route('docs.search', ['q' => 'Authentication', 'version' => 'v1']))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Authentication Guide', 'type' => 'page']);

        $this->getJson(route('docs.search', ['q' => 'Core', 'version' => 'v1']))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Core', 'type' => 'category']);
    }

    public function test_deleting_model_removes_search_index_row(): void
    {
        $page = DocumentationPage::query()->where('slug', 'authentication')->firstOrFail();

        $this->assertDatabaseHas('search_index', [
            'searchable_type' => DocumentationPage::class,
            'searchable_id' => $page->id,
        ]);

        $page->delete();

        $this->assertDatabaseMissing('search_index', [
            'searchable_type' => DocumentationPage::class,
            'searchable_id' => $page->id,
        ]);
    }

    public function test_search_requires_minimum_characters(): void
    {
        $this->getJson(route('docs.search', ['q' => 't']))
            ->assertOk()
            ->assertJsonPath('results', []);
    }

    public function test_docs_layout_renders_live_search_input(): void
    {
        $this->get(route('docs.overview'))
            ->assertOk()
            ->assertSee('Search API…')
            ->assertDontSee('Search API… (Module 9)')
            ->assertSee('docsSearch');
    }

    public function test_reindex_command_rebuilds_index(): void
    {
        SearchIndex::query()->delete();

        $this->artisan('search:reindex')
            ->assertSuccessful();

        $this->assertTrue(SearchIndex::query()->where('type', 'page')->exists());
        $this->assertTrue(SearchIndex::query()->where('type', 'category')->exists());
    }
}
