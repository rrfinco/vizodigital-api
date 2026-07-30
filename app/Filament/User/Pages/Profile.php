<?php

namespace App\Filament\User\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Profile extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Account';

    protected static ?int $navigationSort = 90;

    protected static ?string $navigationLabel = 'Profile';

    protected static ?string $title = 'My profile';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.user.pages.profile';
}
