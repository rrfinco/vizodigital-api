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
        // Getting Started is Overview (hardcoded) + CMS page links like Authentication.
        // Explorer / FAQs / Changelog / SDK live under the Reference section in SidebarBuilder —
        // do not seed them here or they duplicate in Getting Started.
        NavigationItem::query()->updateOrCreate(
            [
                'api_version_id' => null,
                'label' => 'Overview',
                'parent_id' => null,
            ],
            [
                'target_type' => NavigationTargetType::Url,
                'route_name' => 'docs.overview',
                'url' => null,
                'is_visible' => true,
                'sort_order' => 1,
            ]
        );

        // Hide legacy duplicate Reference links if an older seed created them.
        NavigationItem::query()
            ->where(function ($query) use ($version): void {
                $query->where('api_version_id', $version->id)
                    ->orWhereNull('api_version_id');
            })
            ->where(function ($query): void {
                $query->where('target_type', NavigationTargetType::Explorer)
                    ->orWhereIn('route_name', [
                        'docs.faqs.index',
                        'docs.changelog.index',
                        'docs.sdk.index',
                    ])
                    ->orWhereIn('label', ['API Explorer', 'FAQs', 'Changelog', 'SDKs', 'SDK']);
            })
            ->update(['is_visible' => false]);
    }
}
