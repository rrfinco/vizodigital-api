<?php

namespace App\Filament\Widgets;

use App\Models\ApiCategory;
use App\Models\ApiEndpoint;
use App\Models\ApiEnvironment;
use App\Models\ApiVersion;
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
            Stat::make('Endpoints', ApiEndpoint::query()->count())
                ->description('Documented APIs'),
        ];
    }
}
