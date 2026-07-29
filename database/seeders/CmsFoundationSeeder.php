<?php

namespace Database\Seeders;

use App\Enums\EnvironmentSlug;
use App\Enums\NavigationTargetType;
use App\Enums\PublishStatus;
use App\Enums\SectionKey;
use App\Models\ApiEnvironment;
use App\Models\ApiVersion;
use App\Models\NavigationItem;
use App\Models\SectionDefinition;
use Illuminate\Database\Seeder;

class CmsFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedEnvironments();
        $version = $this->seedDefaultVersion();
        $this->attachEnvironments($version);
        $this->seedSectionDefinitions();
        $this->seedNavigation($version);
    }

    private function seedEnvironments(): void
    {
        ApiEnvironment::query()->updateOrCreate(
            ['slug' => EnvironmentSlug::Uat->value],
            [
                'name' => 'UAT',
                'label' => EnvironmentSlug::Uat->label(),
                'base_url' => config('portal.environments.uat.base_url'),
                'badge' => EnvironmentSlug::Uat->badge(),
                'color' => '#2563EB',
                'is_default' => true,
                'is_enabled' => true,
                'sort_order' => 1,
            ]
        );

        ApiEnvironment::query()->updateOrCreate(
            ['slug' => EnvironmentSlug::Production->value],
            [
                'name' => 'Production',
                'label' => EnvironmentSlug::Production->label(),
                'base_url' => config('portal.environments.production.base_url'),
                'badge' => EnvironmentSlug::Production->badge(),
                'color' => '#22C55E',
                'is_default' => false,
                'is_enabled' => true,
                'sort_order' => 2,
            ]
        );
    }

    private function seedDefaultVersion(): ApiVersion
    {
        return ApiVersion::query()->updateOrCreate(
            ['slug' => 'v1'],
            [
                'name' => 'Version 1',
                'status' => PublishStatus::Draft,
                'is_default' => true,
                'description' => 'Default API version shell. Publish content from the admin CMS (Module 3+).',
                'sort_order' => 1,
            ]
        );
    }

    private function attachEnvironments(ApiVersion $version): void
    {
        $ids = ApiEnvironment::query()
            ->whereIn('slug', [EnvironmentSlug::Uat->value, EnvironmentSlug::Production->value])
            ->pluck('id');

        $version->environments()->syncWithoutDetaching($ids->all());
    }

    private function seedSectionDefinitions(): void
    {
        foreach (SectionKey::cases() as $index => $key) {
            SectionDefinition::query()->updateOrCreate(
                ['key' => $key->value],
                [
                    'label' => $key->label(),
                    'component' => $key->component(),
                    'default_config' => [],
                    'is_system' => true,
                    'is_enabled_by_default' => ! in_array($key, [SectionKey::Webhooks, SectionKey::TryApi], true),
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    private function seedNavigation(ApiVersion $version): void
    {
        $items = [
            [
                'api_version_id' => null,
                'label' => 'Overview',
                'target_type' => NavigationTargetType::Url,
                'route_name' => 'docs.overview',
                'sort_order' => 1,
            ],
            [
                'api_version_id' => $version->id,
                'label' => 'API Explorer',
                'target_type' => NavigationTargetType::Explorer,
                'route_name' => null,
                'sort_order' => 2,
            ],
            [
                'api_version_id' => $version->id,
                'label' => 'FAQs',
                'target_type' => NavigationTargetType::Url,
                'route_name' => 'docs.faqs.index',
                'sort_order' => 3,
            ],
            [
                'api_version_id' => $version->id,
                'label' => 'Changelog',
                'target_type' => NavigationTargetType::Url,
                'route_name' => 'docs.changelog.index',
                'sort_order' => 4,
            ],
            [
                'api_version_id' => $version->id,
                'label' => 'SDKs',
                'target_type' => NavigationTargetType::Url,
                'route_name' => 'docs.sdk.index',
                'sort_order' => 5,
            ],
        ];

        foreach ($items as $item) {
            NavigationItem::query()->updateOrCreate(
                [
                    'api_version_id' => $item['api_version_id'],
                    'label' => $item['label'],
                    'parent_id' => null,
                ],
                [
                    'target_type' => $item['target_type'],
                    'route_name' => $item['route_name'],
                    'url' => null,
                    'is_visible' => true,
                    'sort_order' => $item['sort_order'],
                ]
            );
        }
    }
}
