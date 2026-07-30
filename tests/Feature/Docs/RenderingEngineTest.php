<?php

namespace Tests\Feature\Docs;

use App\Actions\Documentation\PublishEndpoint;
use App\Enums\DocPageType;
use App\Enums\HttpMethod;
use App\Enums\ParameterLocation;
use App\Enums\PublishStatus;
use App\Enums\Role;
use App\Enums\SectionKey;
use App\Enums\SnippetLanguage;
use App\Models\ApiCategory;
use App\Models\ApiEndpoint;
use App\Models\ApiEnvironment;
use App\Models\ApiGroup;
use App\Models\ApiVersion;
use App\Models\DocumentationPage;
use App\Models\User;
use Database\Seeders\CmsFoundationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenderingEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ApiEndpoint $endpoint;

    private ApiEndpoint $related;

    private ApiVersion $version;

    private ApiEnvironment $uat;

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
            'description_md' => "## Overview\n\nCreates a bearer token.",
            'permission_name' => 'auth.token',
            'rate_limit' => '60 requests / minute',
            'status' => PublishStatus::Draft,
            'sort_order' => 1,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $this->endpoint->headers()->create([
            'name' => 'Authorization',
            'type' => 'string',
            'required' => true,
            'description' => 'Basic credentials',
            'sort_order' => 1,
        ]);

        $this->endpoint->parameters()->create([
            'name' => 'scope',
            'location' => ParameterLocation::Query,
            'type' => 'string',
            'required' => false,
            'sort_order' => 1,
        ]);

        $this->endpoint->requestBodies()->create([
            'content_type' => 'application/json',
            'description' => 'Token request',
            'example' => ['grant_type' => 'client_credentials'],
            'required' => true,
            'sort_order' => 1,
        ]);

        $this->endpoint->responses()->create([
            'status_code' => 200,
            'description' => 'Token issued',
            'content_type' => 'application/json',
            'example' => ['token' => 'abc'],
            'is_default' => true,
            'sort_order' => 1,
        ]);

        $this->endpoint->errors()->create([
            'error_code' => 'invalid_client',
            'status_code' => 401,
            'message' => 'Invalid client',
            'sort_order' => 1,
        ]);

        $this->endpoint->notes()->create([
            'body_md' => 'Store tokens securely.',
            'sort_order' => 1,
        ]);

        $this->endpoint->examples()->create([
            'api_environment_id' => $this->uat->id,
            'title' => 'UAT success',
            'request' => ['grant_type' => 'client_credentials'],
            'response' => ['access_token' => 'uat-token'],
            'response_status' => 200,
            'sort_order' => 1,
        ]);

        $this->endpoint->codeSamples()->create([
            'api_environment_id' => $this->uat->id,
            'language' => SnippetLanguage::Curl,
            'code' => 'curl -X POST https://uat.example/v1/auth/token',
            'sort_order' => 1,
        ]);

        $this->related = ApiEndpoint::query()->create([
            'api_group_id' => $group->id,
            'api_version_id' => $this->version->id,
            'name' => 'Revoke Token',
            'slug' => 'revoke-token',
            'method' => HttpMethod::Post,
            'path' => '/v1/auth/revoke',
            'summary' => 'Revoke a token',
            'status' => PublishStatus::Draft,
            'sort_order' => 2,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $this->endpoint->relatedEndpoints()->attach($this->related->id, [
            'label' => 'Also see revoke',
            'sort_order' => 1,
        ]);
    }

    public function test_published_endpoint_renders_enabled_sections_in_order(): void
    {
        $this->actingAs($this->admin);
        app(PublishEndpoint::class)($this->endpoint);
        app(PublishEndpoint::class)($this->related);

        $this->endpoint->sections()
            ->where('section_key', SectionKey::Headers->value)
            ->update(['enabled' => false]);

        $response = $this->get(route('docs.endpoints.show', [
            'version' => 'v1',
            'endpoint' => 'get-token',
        ]));

        $response->assertOk()
            ->assertSee('Get Token')
            ->assertSee('Creates a bearer token')
            ->assertSee('scope')
            ->assertSee('application/json')
            ->assertSee('Token issued')
            ->assertSee('invalid_client')
            ->assertSee('Store tokens securely')
            ->assertSee('UAT success')
            ->assertSee('curl -X POST')
            ->assertSee('60 requests / minute')
            ->assertSee('auth.token')
            ->assertSee('Also see revoke')
            ->assertDontSee('Authorization', false);

        $html = $response->getContent();
        $this->assertTrue(
            strpos($html, 'id="parameters"') < strpos($html, 'id="body"')
            && strpos($html, 'id="body"') < strpos($html, 'id="responses"')
        );
    }

    public function test_disabled_section_is_omitted_from_toc(): void
    {
        $this->actingAs($this->admin);
        app(PublishEndpoint::class)($this->endpoint);

        $this->endpoint->sections()
            ->where('section_key', SectionKey::Errors->value)
            ->update(['enabled' => false]);

        $this->get(route('docs.endpoints.show', [
            'version' => 'v1',
            'endpoint' => 'get-token',
        ]))
            ->assertOk()
            ->assertDontSee('invalid_client')
            ->assertDontSee('href="#errors"', false);
    }

    public function test_related_draft_endpoints_are_hidden_on_public_page(): void
    {
        $this->actingAs($this->admin);
        app(PublishEndpoint::class)($this->endpoint);

        $this->get(route('docs.endpoints.show', [
            'version' => 'v1',
            'endpoint' => 'get-token',
        ]))
            ->assertOk()
            ->assertDontSee('Also see revoke');
    }

    public function test_published_documentation_page_renders_markdown_blocks(): void
    {
        $page = DocumentationPage::query()->create([
            'api_version_id' => $this->version->id,
            'type' => DocPageType::Authentication,
            'title' => 'Authentication Guide',
            'slug' => 'authentication',
            'body_md' => "Start with an API key.\n\n**Required** for all calls.",
            'status' => PublishStatus::Published,
            'published_at' => now(),
            'sort_order' => 1,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $page->sections()->create([
            'section_key' => 'bearer_tokens',
            'title' => 'Bearer tokens',
            'body_md' => 'Pass `Authorization: Bearer`.',
            'enabled' => true,
            'sort_order' => 1,
        ]);

        $page->sections()->create([
            'section_key' => 'disabled_block',
            'title' => 'Hidden',
            'body_md' => 'Should not render',
            'enabled' => false,
            'sort_order' => 2,
        ]);

        $this->get(route('docs.pages.show', [
            'version' => 'v1',
            'page' => 'authentication',
        ]))
            ->assertOk()
            ->assertSee('Authentication Guide')
            ->assertSee('Start with an API key')
            ->assertSee('Bearer tokens')
            ->assertSee('Authorization: Bearer')
            ->assertDontSee('Should not render');
    }

    public function test_draft_documentation_page_is_not_public(): void
    {
        DocumentationPage::query()->create([
            'api_version_id' => $this->version->id,
            'type' => DocPageType::Guide,
            'title' => 'Draft Guide',
            'slug' => 'draft-guide',
            'body_md' => 'Secret',
            'status' => PublishStatus::Draft,
            'sort_order' => 1,
        ]);

        $this->get(route('docs.pages.show', [
            'version' => 'v1',
            'page' => 'draft-guide',
        ]))->assertNotFound();
    }

    public function test_staff_can_preview_draft_documentation_page(): void
    {
        $page = DocumentationPage::query()->create([
            'api_version_id' => $this->version->id,
            'type' => DocPageType::Guide,
            'title' => 'Draft Guide',
            'slug' => 'draft-guide',
            'body_md' => 'Secret draft content',
            'status' => PublishStatus::Draft,
            'sort_order' => 1,
        ]);

        $this->actingAs($this->admin)
            ->get(route('docs.preview.pages.show', $page))
            ->assertOk()
            ->assertSee('Preview mode')
            ->assertSee('Secret draft content');
    }
}
