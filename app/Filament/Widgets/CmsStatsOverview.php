<?php

namespace App\Filament\Widgets;

use App\Models\ApiCategory;
use App\Models\ApiEndpoint;
use App\Models\ApiEnvironment;
use App\Models\ApiGroup;
use App\Models\ApiVersion;
use App\Models\ChangelogEntry;
use App\Models\CodeSample;
use App\Models\DocumentationPage;
use App\Models\EndpointExample;
use App\Models\Faq;
use App\Models\MediaAsset;
use App\Models\PostmanCollection;
use App\Models\SdkPackage;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CmsStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Versions', ApiVersion::query()->count()),
            Stat::make('Environments', ApiEnvironment::query()->count()),
            Stat::make('Categories', ApiCategory::query()->count()),
            Stat::make('Groups', ApiGroup::query()->count()),
            Stat::make('Endpoints', ApiEndpoint::query()->count())
                ->description('CMS-managed · none hardcoded'),
            Stat::make('Pages', DocumentationPage::query()->count()),
            Stat::make('FAQs / Changelog', Faq::query()->count().' / '.ChangelogEntry::query()->count()),
            Stat::make('Media', MediaAsset::query()->count()),
            Stat::make('Env examples', EndpointExample::query()->count()),
            Stat::make('Code samples', CodeSample::query()->count()),
            Stat::make('Postman / SDKs', PostmanCollection::query()->count().' / '.SdkPackage::query()->count()),
        ];
    }
}
