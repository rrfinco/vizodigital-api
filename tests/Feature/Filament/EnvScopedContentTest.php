<?php

namespace Tests\Feature\Filament;

use App\Enums\EnvironmentSlug;
use App\Enums\HttpMethod;
use App\Enums\PublishStatus;
use App\Enums\Role;
use App\Enums\SnippetLanguage;
use App\Filament\RelationManagers\BaseUrlsRelationManager;
use App\Filament\Resources\ApiEndpoints\ApiEndpointResource;
use App\Filament\Resources\ApiEndpoints\Pages\EditApiEndpoint;
use App\Filament\Resources\ApiEndpoints\RelationManagers\CodeSamplesRelationManager;
use App\Filament\Resources\ApiEndpoints\RelationManagers\ExamplesRelationManager;
use App\Filament\Resources\PostmanCollections\PostmanCollectionResource;
use App\Filament\Resources\SdkPackages\Pages\CreateSdkPackage;
use App\Filament\Resources\SdkPackages\SdkPackageResource;
use App\Models\ApiCategory;
use App\Models\ApiEndpoint;
use App\Models\ApiEnvironment;
use App\Models\ApiGroup;
use App\Models\ApiVersion;
use App\Models\User;
use App\Services\Environment\BaseUrlResolver;
use Database\Seeders\CmsFoundationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EnvScopedContentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ApiEndpoint $endpoint;

    private ApiEnvironment $uat;

    private ApiEnvironment $production;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CmsFoundationSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::SuperAdmin->value);

        $this->uat = ApiEnvironment::query()->where('slug', EnvironmentSlug::Uat->value)->firstOrFail();
        $this->production = ApiEnvironment::query()->where('slug', EnvironmentSlug::Production->value)->firstOrFail();

        $version = ApiVersion::query()->where('slug', 'v1')->firstOrFail();

        $category = ApiCategory::query()->create([
            'api_version_id' => $version->id,
            'name' => 'Core',
            'slug' => 'core',
            'status' => PublishStatus::Draft,
            'sort_order' => 1,
        ]);

        $group = ApiGroup::query()->create([
            'api_category_id' => $category->id,
            'name' => 'Auth',
            'slug' => 'auth',
            'status' => PublishStatus::Draft,
            'sort_order' => 1,
        ]);

        $this->endpoint = ApiEndpoint::query()->create([
            'api_group_id' => $group->id,
            'api_version_id' => $version->id,
            'name' => 'Get Token',
            'slug' => 'get-token',
            'method' => HttpMethod::Post,
            'path' => '/v1/auth/token',
            'status' => PublishStatus::Draft,
            'sort_order' => 1,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
    }

    public function test_endpoint_resource_registers_env_scoped_relation_managers(): void
    {
        $relations = ApiEndpointResource::getRelations();

        $this->assertContains(ExamplesRelationManager::class, $relations);
        $this->assertContains(CodeSamplesRelationManager::class, $relations);
        $this->assertFalse(PostmanCollectionResource::shouldRegisterNavigation());
        $this->assertFalse(SdkPackageResource::shouldRegisterNavigation());
        $this->assertSame('Postman', PostmanCollectionResource::getNavigationLabel());
        $this->assertSame('SDK packages', SdkPackageResource::getNavigationLabel());
    }

    public function test_admin_can_create_env_scoped_example_and_code_sample(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ExamplesRelationManager::class, [
            'ownerRecord' => $this->endpoint,
            'pageClass' => EditApiEndpoint::class,
        ])
            ->callTableAction('create', data: [
                'api_environment_id' => $this->uat->id,
                'title' => 'Successful token',
                'response_status' => 200,
                'description' => 'UAT happy path',
                'request' => "{\"email\":\"dev@example.com\"}",
                'response' => "{\"token\":\"uat-token\"}",
                'sort_order' => 1,
            ])
            ->assertHasNoTableActionErrors();

        Livewire::test(CodeSamplesRelationManager::class, [
            'ownerRecord' => $this->endpoint,
            'pageClass' => EditApiEndpoint::class,
        ])
            ->callTableAction('create', data: [
                'api_environment_id' => $this->uat->id,
                'language' => SnippetLanguage::Curl->value,
                'code' => 'curl -X POST "$BASE_URL/v1/auth/token"',
                'is_generated' => false,
                'is_override' => true,
                'sort_order' => 1,
            ])
            ->assertHasNoTableActionErrors();

        $this->endpoint->refresh();

        $this->assertSame(1, $this->endpoint->examples()->count());
        $this->assertSame($this->uat->id, $this->endpoint->examples()->first()->api_environment_id);
        $this->assertSame(['token' => 'uat-token'], $this->endpoint->examples()->first()->response);
        $this->assertSame(1, $this->endpoint->codeSamples()->count());
        $this->assertSame(SnippetLanguage::Curl, $this->endpoint->codeSamples()->first()->language);
    }

    public function test_base_url_resolver_respects_cascade_overrides(): void
    {
        $resolver = app(BaseUrlResolver::class);

        $this->assertSame(
            rtrim((string) $this->uat->base_url, '/'),
            $resolver->forEndpoint($this->endpoint, $this->uat)
        );

        $this->endpoint->group->baseUrls()->create([
            'api_environment_id' => $this->uat->id,
            'base_url' => 'https://group-uat.example.com/',
        ]);

        $this->assertSame(
            'https://group-uat.example.com',
            $resolver->forEndpoint($this->endpoint->fresh(['group.category.version']), $this->uat)
        );

        $this->endpoint->baseUrls()->create([
            'api_environment_id' => $this->uat->id,
            'base_url' => 'https://endpoint-uat.example.com/',
        ]);

        $this->assertSame(
            'https://endpoint-uat.example.com',
            $resolver->forEndpoint($this->endpoint->fresh(['group.category.version']), $this->uat)
        );

        $this->assertSame(
            rtrim((string) $this->production->base_url, '/'),
            $resolver->forEndpoint($this->endpoint->fresh(['group.category.version']), $this->production)
        );
    }

    public function test_admin_can_create_postman_collection_and_sdk_package(): void
    {
        $this->actingAs($this->admin);

        $version = ApiVersion::query()->where('slug', 'v1')->firstOrFail();

        Livewire::test(CreateSdkPackage::class)
            ->fillForm([
                'api_version_id' => $version->id,
                'name' => 'PHP SDK',
                'slug' => 'php-sdk',
                'language' => SnippetLanguage::Php->value,
                'status' => PublishStatus::Draft->value,
                'package_name' => 'acme/portal-sdk',
                'repo_url' => 'https://github.com/acme/portal-sdk',
                'install_md' => 'composer require acme/portal-sdk',
                'sort_order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('sdk_packages', [
            'slug' => 'php-sdk',
            'language' => SnippetLanguage::Php->value,
            'package_name' => 'acme/portal-sdk',
        ]);

        $this->assertDatabaseHas('api_environments', [
            'slug' => EnvironmentSlug::Uat->value,
        ]);

        \App\Models\PostmanCollection::query()->create([
            'api_version_id' => $version->id,
            'api_environment_id' => $this->uat->id,
            'name' => 'UAT Collection',
            'slug' => 'uat-collection',
            'status' => PublishStatus::Draft,
            'payload' => ['info' => ['name' => 'UAT']],
        ]);

        $this->assertDatabaseHas('postman_collections', [
            'slug' => 'uat-collection',
            'api_environment_id' => $this->uat->id,
        ]);
    }

    public function test_admin_can_set_endpoint_base_url_override(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(BaseUrlsRelationManager::class, [
            'ownerRecord' => $this->endpoint,
            'pageClass' => EditApiEndpoint::class,
        ])
            ->callTableAction('create', data: [
                'api_environment_id' => $this->production->id,
                'base_url' => 'https://live-api.example.com',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('endpoint_base_urls', [
            'api_environment_id' => $this->production->id,
            'urlable_type' => ApiEndpoint::class,
            'urlable_id' => $this->endpoint->id,
            'base_url' => 'https://live-api.example.com',
        ]);
    }
}
