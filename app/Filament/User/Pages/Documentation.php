<?php

namespace App\Filament\User\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Documentation extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 12;

    protected static ?string $navigationLabel = 'API Docs';

    protected static ?string $title = 'API Documentation';

    protected string $view = 'filament.user.pages.documentation';
}
