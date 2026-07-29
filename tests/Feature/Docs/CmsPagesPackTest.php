<?php

namespace Tests\Feature\Docs;

use App\Enums\DocPageType;
use App\Enums\PublishStatus;
use App\Enums\Role;
use App\Enums\SnippetLanguage;
use App\Models\ApiVersion;
use App\Models\ChangelogEntry;
use App\Models\DocumentationPage;
use App\Models\Faq;
use App\Models\MediaAsset;
use App\Models\SdkPackage;
use App\Models\User;
use Database\Seeders\CmsFoundationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsPagesPackTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

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
    }

    public function test_published_faq_changelog_and_sdk_hub_are_public(): void
    {
        Faq::query()->create([
            'api_version_id' => $this->version->id,
            'question' => 'How do I authenticate?',
            'answer_md' => 'Use a **bearer token**.',
            'category' => 'Authentication',
            'status' => PublishStatus::Published,
            'sort_order' => 1,
        ]);

        ChangelogEntry::query()->create([
            'api_version_id' => $this->version->id,
            'title' => 'v1.0.0 launch',
            'slug' => 'v1-0-0-launch',
            'body_md' => 'Initial public release.',
            'status' => PublishStatus::Published,
            'released_at' => now(),
            'sort_order' => 1,
        ]);

        SdkPackage::query()->create([
            'api_version_id' => $this->version->id,
            'name' => 'PHP SDK',
            'slug' => 'php-sdk',
            'language' => SnippetLanguage::Php,
            'status' => PublishStatus::Published,
            'install_md' => '`composer require acme/sdk`',
            'package_name' => 'acme/sdk',
            'sort_order' => 1,
        ]);

        $this->get(route('docs.faqs.index', ['version' => 'v1']))
            ->assertOk()
            ->assertSee('How do I authenticate?')
            ->assertSee('bearer token');

        $this->get(route('docs.changelog.index', ['version' => 'v1']))
            ->assertOk()
            ->assertSee('v1.0.0 launch');

        $this->get(route('docs.changelog.show', ['version' => 'v1', 'entry' => 'v1-0-0-launch']))
            ->assertOk()
            ->assertSee('Initial public release');

        $this->get(route('docs.sdk.index', ['version' => 'v1']))
            ->assertOk()
            ->assertSee('PHP SDK')
            ->assertSee('acme/sdk');
    }

    public function test_draft_faq_and_changelog_are_hidden(): void
    {
        Faq::query()->create([
            'api_version_id' => $this->version->id,
            'question' => 'Secret FAQ',
            'answer_md' => 'Hidden',
            'status' => PublishStatus::Draft,
            'sort_order' => 1,
        ]);

        ChangelogEntry::query()->create([
            'api_version_id' => $this->version->id,
            'title' => 'Draft release',
            'slug' => 'draft-release',
            'body_md' => 'Not ready',
            'status' => PublishStatus::Draft,
            'sort_order' => 1,
        ]);

        $this->get(route('docs.faqs.index', ['version' => 'v1']))
            ->assertOk()
            ->assertDontSee('Secret FAQ');

        $this->get(route('docs.changelog.show', ['version' => 'v1', 'entry' => 'draft-release']))
            ->assertNotFound();
    }

    public function test_webhooks_documentation_page_renders_when_published(): void
    {
        $page = DocumentationPage::query()->create([
            'api_version_id' => $this->version->id,
            'type' => DocPageType::Webhooks,
            'title' => 'Webhooks',
            'slug' => 'webhooks',
            'body_md' => 'Receive async events.',
            'status' => PublishStatus::Published,
            'published_at' => now(),
            'sort_order' => 1,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $page->sections()->create([
            'section_key' => 'signature',
            'title' => 'Signatures',
            'body_md' => 'Verify HMAC signatures.',
            'enabled' => true,
            'sort_order' => 1,
        ]);

        $this->get(route('docs.pages.show', ['version' => 'v1', 'page' => 'webhooks']))
            ->assertOk()
            ->assertSee('Webhooks')
            ->assertSee('Receive async events')
            ->assertSee('Signatures');
    }

    public function test_search_indexes_published_faq_and_changelog_urls(): void
    {
        $faq = Faq::query()->create([
            'api_version_id' => $this->version->id,
            'question' => 'Rate limit FAQ',
            'answer_md' => '60 requests per minute',
            'category' => 'Limits',
            'status' => PublishStatus::Published,
            'sort_order' => 1,
        ]);

        ChangelogEntry::query()->create([
            'api_version_id' => $this->version->id,
            'title' => 'Rate limit bump',
            'slug' => 'rate-limit-bump',
            'body_md' => 'Increased limits',
            'status' => PublishStatus::Published,
            'released_at' => now(),
            'sort_order' => 1,
        ]);

        $this->getJson(route('docs.search', ['q' => 'Rate limit', 'version' => 'v1']))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Rate limit FAQ', 'type' => 'faq'])
            ->assertJsonFragment(['title' => 'Rate limit bump', 'type' => 'changelog']);

        $this->assertDatabaseHas('search_index', [
            'searchable_type' => Faq::class,
            'searchable_id' => $faq->id,
        ]);

        $this->assertNotNull(
            \App\Models\SearchIndex::query()
                ->where('searchable_type', Faq::class)
                ->where('searchable_id', $faq->id)
                ->value('url')
        );
    }

    public function test_seeded_navigation_includes_cms_pack_links(): void
    {
        $this->get(route('docs.overview'))
            ->assertOk()
            ->assertSee('FAQs')
            ->assertSee('Changelog')
            ->assertSee('SDKs');
    }

    public function test_media_asset_can_be_persisted(): void
    {
        $asset = MediaAsset::query()->create([
            'disk' => 'public',
            'path' => 'portal-media/example.png',
            'original_name' => 'example.png',
            'mime_type' => 'image/png',
            'size' => 1024,
            'alt' => 'Example diagram',
            'uploaded_by' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('media_assets', [
            'id' => $asset->id,
            'original_name' => 'example.png',
            'alt' => 'Example diagram',
        ]);
    }
}
