<?php

namespace App\Filament\User\Pages;

use App\Filament\User\Widgets\DeveloperHeroCards;
use App\Filament\User\Widgets\DeveloperShortcodesWidget;
use App\Filament\User\Widgets\DeveloperTransactionHistory;
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

    protected static ?string $title = 'Developer Dashboard';

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
            DeveloperHeroCards::class,
            DeveloperTransactionHistory::class,
            DeveloperShortcodesWidget::class,
        ];
    }
}
