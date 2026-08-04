<?php

namespace App\Filament\Partner\Pages;

use App\Filament\Partner\Widgets\PartnerHeroCards;
use App\Filament\Partner\Widgets\PartnerRecentFloatActivity;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static string|UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Partner Dashboard';

    public function getColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }

    public function getWidgets(): array
    {
        return [
            PartnerHeroCards::class,
            PartnerRecentFloatActivity::class,
        ];
    }
}
