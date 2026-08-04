<?php

namespace Tests\Feature\Whitelabel;

use App\Actions\Documentation\PublishEndpoint;
use App\Enums\EnvironmentSlug;
use App\Enums\HttpMethod;
use App\Enums\PublishStatus;
use App\Enums\Role;
use App\Enums\SnippetLanguage;
use App\Enums\WhitelabelDomainRole;
use App\Models\ApiCategory;
use App\Models\ApiEndpoint;
use App\Models\ApiEnvironment;
use App\Models\ApiGroup;
use App\Models\ApiVersion;
use App\Models\User;
use App\Models\Whitelabel;
use App\Models\WhitelabelDomain;
use App\Services\Portal\PortalContext;
use App\Services\Whitelabel\WhitelabelContext;
use App\Services\Whitelabel\WhitelabelEnvironmentUrls;
use Database\Seeders\CmsFoundationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhitelabelEnvironmentUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(CmsFoundationSeeder::class);
    }

    public function test_domain_role_defaults_to_portal_and_resolves_api_base_urls(): void
    {
        $wl = Whitelabel::factory()->create(['brand_name' => 'Acme']);

        WhitelabelDomain::query()->create([
            'whitelabel_id' => $wl->id,
            'host' => 'portal.acme.test',
            'role' => WhitelabelDomainRole::Portal,
            'is_primary' => true,
        ]);
        WhitelabelDomain::query()->create([
            'whitelabel_id' => $wl->id,
            'host' => 'uat.acme.test',
            'role' => WhitelabelDomainRole::Uat,
            'is_primary' => true,
        ]);
        WhitelabelDomain::query()->create([
            'whitelabel_id' => $wl->id,
            'host' => 'api.acme.test',
            'role' => WhitelabelDomainRole::Production,
            'is_primary' => true,
        ]);

        $wl->load('domains');

        $this->assertSame('https://uat.acme.test', $wl->baseUrlForRole(WhitelabelDomainRole::Uat));
        $this->assertSame('https://api.acme.test', $wl->baseUrlForRole(WhitelabelDomainRole::Production));
        $this->assertSame('https://portal.acme.test', $wl->baseUrlForRole(WhitelabelDomainRole::Portal));
    }

    public function test_docs_on_whitelabel_portal_host_use_partner_api_base_urls(): void
    {
        $wl = Whitelabel::factory()->create(['brand_name' => 'Acme Docs']);

        WhitelabelDomain::query()->create([
            'whitelabel_id' => $wl->id,
            'host' => 'portal.acme.test',
            'role' => WhitelabelDomainRole::Portal,
            'is_primary' => true,
        ]);
        WhitelabelDomain::query()->create([
            'whitelabel_id' => $wl->id,
            'host' => 'uat.acme.test',
            'role' => WhitelabelDomainRole::Uat,
            'is_primary' => true,
        ]);
        WhitelabelDomain::query()->create([
            'whitelabel_id' => $wl->id,
            'host' => 'api.acme.test',
            'role' => WhitelabelDomainRole::Production,
            'is_primary' => true,
        ]);

        $platformUat = (string) config('portal.environments.uat.base_url');

        $this->get('http://portal.acme.test/docs?env=uat')
            ->assertOk()
            ->assertSee('https://uat.acme.test')
            ->assertDontSee($platformUat);

        $this->get('http://portal.acme.test/docs?env=production')
            ->assertOk()
            ->assertSee('https://api.acme.test');
    }

    public function test_platform_docs_keep_global_environment_base_urls(): void
    {
        $this->get('/docs?env=uat')
            ->assertOk()
            ->assertSee(config('portal.environments.uat.base_url'));
    }

    public function test_portal_context_remaps_environment_base_urls_from_host(): void
    {
        $wl = Whitelabel::factory()->create();
        WhitelabelDomain::query()->create([
            'whitelabel_id' => $wl->id,
            'host' => 'docs.partner.test',
            'role' => WhitelabelDomainRole::Portal,
            'is_primary' => true,
        ]);
        WhitelabelDomain::query()->create([
            'whitelabel_id' => $wl->id,
            'host' => 'sandbox.partner.test',
            'role' => WhitelabelDomainRole::Uat,
            'is_primary' => true,
        ]);

        app(WhitelabelContext::class)->resolveFromHost('docs.partner.test');

        $portal = app(PortalContext::class);
        $portal->resolve(request()->create('http://docs.partner.test/docs', 'GET', ['env' => 'uat']));

        $uat = $portal->environments()->first(
            fn (ApiEnvironment $env): bool => ($env->slug instanceof \BackedEnum ? $env->slug->value : (string) $env->slug) === EnvironmentSlug::Uat->value
        );

        $this->assertNotNull($uat);
        $this->assertSame('https://sandbox.partner.test', $uat->base_url);
    }

    public function test_resolver_falls_back_when_role_domain_missing(): void
    {
        $wl = Whitelabel::factory()->create();
        WhitelabelDomain::query()->create([
            'whitelabel_id' => $wl->id,
            'host' => 'only-portal.partner.test',
            'role' => WhitelabelDomainRole::Portal,
            'is_primary' => true,
        ]);

        $wl->load('domains');
        $uat = ApiEnvironment::query()->where('slug', EnvironmentSlug::Uat)->firstOrFail();

        $resolved = app(WhitelabelEnvironmentUrls::class)->resolve($uat, $wl);

        $this->assertSame(rtrim((string) $uat->base_url, '/'), $resolved);
    }

    public function test_code_samples_rewrite_platform_base_urls_on_whitelabel_host(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SuperAdmin->value);

        $version = ApiVersion::query()->where('slug', 'v1')->firstOrFail();
        $version->update(['status' => PublishStatus::Published]);
        $uat = ApiEnvironment::query()->where('slug', EnvironmentSlug::Uat)->firstOrFail();

        $category = ApiCategory::query()->create([
            'api_version_id' => $version->id,
            'name' => 'Core',
            'slug' => 'core-wl-rewrite',
            'status' => PublishStatus::Published,
            'sort_order' => 1,
            'show_in_sidebar' => true,
        ]);
        $group = ApiGroup::query()->create([
            'api_category_id' => $category->id,
            'name' => 'Auth',
            'slug' => 'auth-wl-rewrite',
            'status' => PublishStatus::Published,
            'sort_order' => 1,
        ]);
        $endpoint = ApiEndpoint::query()->create([
            'api_group_id' => $group->id,
            'api_version_id' => $version->id,
            'name' => 'Get Token WL',
            'slug' => 'get-token-wl',
            'method' => HttpMethod::Post,
            'path' => '/v1/auth/token',
            'summary' => 'Issue an API token',
            'status' => PublishStatus::Draft,
            'sort_order' => 1,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $endpoint->codeSamples()->create([
            'api_environment_id' => $uat->id,
            'language' => SnippetLanguage::Curl,
            'code' => 'curl '.rtrim((string) $uat->base_url, '/').'/v1/auth/token',
            'sort_order' => 1,
        ]);

        $this->actingAs($admin);
        app(PublishEndpoint::class)($endpoint);

        $wl = Whitelabel::factory()->create();
        WhitelabelDomain::query()->create([
            'whitelabel_id' => $wl->id,
            'host' => 'portal.acme.test',
            'role' => WhitelabelDomainRole::Portal,
            'is_primary' => true,
        ]);
        WhitelabelDomain::query()->create([
            'whitelabel_id' => $wl->id,
            'host' => 'uat.acme.test',
            'role' => WhitelabelDomainRole::Uat,
            'is_primary' => true,
        ]);

        $this->get('http://portal.acme.test/docs/v1/endpoints/get-token-wl?env=uat')
            ->assertOk()
            ->assertSee('curl https://uat.acme.test/v1/auth/token')
            ->assertDontSee('curl '.rtrim((string) $uat->base_url, '/').'/v1/auth/token');
    }
}
