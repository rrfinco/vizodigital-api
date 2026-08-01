<?php

namespace App\Filament\User\Pages;

use App\Filament\Concerns\InteractsWithRechargeOperators;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class RechargeOperators extends Page
{
    use InteractsWithRechargeOperators;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static string|UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 19;

    protected static ?string $navigationLabel = 'Recharge Operators';

    protected static ?string $title = 'Recharge Operator SP Keys';

    protected static ?string $slug = 'recharge-operators';

    protected string $view = 'filament.pages.recharge-operators';
}
