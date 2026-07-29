<?php

namespace Tests\Feature\Filament;

use App\Enums\HttpMethod;
use App\Enums\ParameterLocation;
use App\Enums\PublishStatus;
use App\Enums\Role;
use App\Enums\SectionKey;
use App\Filament\RelationManagers\BaseUrlsRelationManager;
use App\Filament\Resources\ApiEndpoints\ApiEndpointResource;
use App\Filament\Resources\ApiEndpoints\Pages\EditApiEndpoint;
use App\Filament\Resources\ApiEndpoints\RelationManagers\CodeSamplesRelationManager;
use App\Filament\Resources\ApiEndpoints\RelationManagers\ErrorsRelationManager;
use App\Filament\Resources\ApiEndpoints\RelationManagers\ExamplesRelationManager;
use App\Filament\Resources\ApiEndpoints\RelationManagers\HeadersRelationManager;
use App\Filament\Resources\ApiEndpoints\RelationManagers\NotesRelationManager;
use App\Filament\Resources\ApiEndpoints\RelationManagers\ParametersRelationManager;
use App\Filament\Resources\ApiEndpoints\RelationManagers\RequestBodiesRelationManager;
use App\Filament\Resources\ApiEndpoints\RelationManagers\ResponsesRelationManager;
use App\Filament\Resources\ApiEndpoints\RelationManagers\SectionsRelationManager;
use App\Models\ApiCategory;
use App\Models\ApiEndpoint;
use App\Models\ApiGroup;
use App\Models\ApiVersion;
use App\Models\EndpointHeader;
use App\Models\EndpointSection;
use App\Models\User;
use Database\Seeders\CmsFoundationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EndpointComposerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ApiEndpoint $endpoint;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CmsFoundationSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::SuperAdmin->value);

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

    public function test_creating_endpoint_seeds_default_section_layout(): void
    {
        $this->assertSame(
            count(SectionKey::defaultLayout()),
            $this->endpoint->sections()->count()
        );

        $this->assertTrue(
            $this->endpoint->sections()
                ->where('section_key', SectionKey::Headers->value)
                ->where('enabled', true)
                ->exists()
        );

        $this->assertTrue(
            $this->endpoint->sections()
                ->where('section_key', SectionKey::TryApi->value)
                ->where('enabled', false)
                ->exists()
        );
    }

    public function test_endpoint_resource_registers_composer_relation_managers(): void
    {
        $relations = ApiEndpointResource::getRelations();

        $this->assertSame([
            SectionsRelationManager::class,
            HeadersRelationManager::class,
            ParametersRelationManager::class,
            RequestBodiesRelationManager::class,
            ResponsesRelationManager::class,
            ErrorsRelationManager::class,
            NotesRelationManager::class,
            ExamplesRelationManager::class,
            CodeSamplesRelationManager::class,
            BaseUrlsRelationManager::class,
        ], $relations);
    }

    public function test_admin_can_create_header_via_relation_manager(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(HeadersRelationManager::class, [
            'ownerRecord' => $this->endpoint,
            'pageClass' => EditApiEndpoint::class,
        ])
            ->callTableAction('create', data: [
                'name' => 'Authorization',
                'type' => 'string',
                'required' => true,
                'example' => 'Bearer {token}',
                'description' => 'API bearer token',
                'sort_order' => 1,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('endpoint_headers', [
            'api_endpoint_id' => $this->endpoint->id,
            'name' => 'Authorization',
            'required' => 1,
        ]);
    }

    public function test_admin_can_create_parameter_body_response_error_and_note(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ParametersRelationManager::class, [
            'ownerRecord' => $this->endpoint,
            'pageClass' => EditApiEndpoint::class,
        ])
            ->callTableAction('create', data: [
                'location' => ParameterLocation::Query->value,
                'name' => 'page',
                'type' => 'integer',
                'required' => false,
                'example' => '1',
                'description' => 'Page number',
                'schema' => null,
                'sort_order' => 1,
            ])
            ->assertHasNoTableActionErrors();

        Livewire::test(RequestBodiesRelationManager::class, [
            'ownerRecord' => $this->endpoint,
            'pageClass' => EditApiEndpoint::class,
        ])
            ->callTableAction('create', data: [
                'content_type' => 'application/json',
                'required' => true,
                'description' => 'Token request',
                'schema' => "{\"type\":\"object\"}",
                'example' => "{\"email\":\"a@b.c\"}",
                'sort_order' => 1,
            ])
            ->assertHasNoTableActionErrors();

        Livewire::test(ResponsesRelationManager::class, [
            'ownerRecord' => $this->endpoint,
            'pageClass' => EditApiEndpoint::class,
        ])
            ->callTableAction('create', data: [
                'status_code' => 200,
                'content_type' => 'application/json',
                'description' => 'OK',
                'is_default' => true,
                'schema' => null,
                'example' => "{\"token\":\"abc\"}",
                'sort_order' => 1,
            ])
            ->assertHasNoTableActionErrors();

        Livewire::test(ErrorsRelationManager::class, [
            'ownerRecord' => $this->endpoint,
            'pageClass' => EditApiEndpoint::class,
        ])
            ->callTableAction('create', data: [
                'error_code' => 'INVALID_CREDENTIALS',
                'status_code' => 401,
                'message' => 'Invalid credentials',
                'description' => 'Wrong email or password',
                'example' => null,
                'sort_order' => 1,
            ])
            ->assertHasNoTableActionErrors();

        Livewire::test(NotesRelationManager::class, [
            'ownerRecord' => $this->endpoint,
            'pageClass' => EditApiEndpoint::class,
        ])
            ->callTableAction('create', data: [
                'body_md' => 'Requires a valid merchant account.',
                'sort_order' => 1,
            ])
            ->assertHasNoTableActionErrors();

        $this->endpoint->refresh();

        $this->assertSame(1, $this->endpoint->parameters()->count());
        $this->assertSame(1, $this->endpoint->requestBodies()->count());
        $this->assertSame(1, $this->endpoint->responses()->count());
        $this->assertSame(1, $this->endpoint->errors()->count());
        $this->assertSame(1, $this->endpoint->notes()->count());
        $this->assertSame(
            ['type' => 'object'],
            $this->endpoint->requestBodies()->first()->schema
        );
    }

    public function test_admin_can_toggle_and_reorder_sections(): void
    {
        $this->actingAs($this->admin);

        /** @var EndpointSection $headersSection */
        $headersSection = $this->endpoint->sections()
            ->where('section_key', SectionKey::Headers->value)
            ->firstOrFail();

        Livewire::test(SectionsRelationManager::class, [
            'ownerRecord' => $this->endpoint,
            'pageClass' => EditApiEndpoint::class,
        ])
            ->callTableAction('edit', $headersSection, data: [
                'section_key' => SectionKey::Headers->value,
                'enabled' => false,
                'config' => "{\"collapsed\":true}",
            ])
            ->assertHasNoTableActionErrors();

        $headersSection->refresh();

        $this->assertFalse($headersSection->enabled);
        $this->assertSame(['collapsed' => true], $headersSection->config);

        $orderedIds = $this->endpoint->sections()
            ->orderBy('sort_order')
            ->pluck('id')
            ->all();

        $reordered = array_reverse($orderedIds);

        Livewire::test(SectionsRelationManager::class, [
            'ownerRecord' => $this->endpoint,
            'pageClass' => EditApiEndpoint::class,
        ])
            ->call('reorderTable', $reordered)
            ->assertSuccessful();

        $this->assertSame(
            $reordered,
            $this->endpoint->sections()->orderBy('sort_order')->pluck('id')->all()
        );
    }

    public function test_headers_are_reorderable(): void
    {
        $this->actingAs($this->admin);

        $first = EndpointHeader::query()->create([
            'api_endpoint_id' => $this->endpoint->id,
            'name' => 'X-First',
            'type' => 'string',
            'required' => false,
            'sort_order' => 1,
        ]);

        $second = EndpointHeader::query()->create([
            'api_endpoint_id' => $this->endpoint->id,
            'name' => 'X-Second',
            'type' => 'string',
            'required' => false,
            'sort_order' => 2,
        ]);

        Livewire::test(HeadersRelationManager::class, [
            'ownerRecord' => $this->endpoint,
            'pageClass' => EditApiEndpoint::class,
        ])
            ->call('reorderTable', [$second->id, $first->id])
            ->assertSuccessful();

        $this->assertSame(
            [$second->id, $first->id],
            $this->endpoint->headers()->orderBy('sort_order')->pluck('id')->all()
        );
    }
}
