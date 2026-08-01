<?php

namespace App\Filament\User\Widgets;

use App\Filament\User\Pages\InspayOperators;
use App\Filament\User\Pages\RechargeOperators;
use Filament\Widgets\Widget;

class DeveloperShortcodesWidget extends Widget
{
    protected string $view = 'filament.user.widgets.developer-shortcodes-widget';

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected static ?int $sort = 3;

    /**
     * @return list<array{name: string, category: string, sp_key: string, type: string}>
     */
    public function getPopularShortcodes(): array
    {
        return [
            ['name' => 'Airtel Prepaid', 'category' => 'Mobile', 'sp_key' => '116', 'type' => 'mobile'],
            ['name' => 'Jio Prepaid', 'category' => 'Mobile', 'sp_key' => '116', 'type' => 'mobile'],
            ['name' => 'Vi Prepaid', 'category' => 'Mobile', 'sp_key' => '37', 'type' => 'mobile'],
            ['name' => 'BSNL Prepaid', 'category' => 'Mobile', 'sp_key' => '4', 'type' => 'mobile'],
            ['name' => 'Airtel Digital TV', 'category' => 'DTH', 'sp_key' => '51', 'type' => 'dth'],
            ['name' => 'Dish TV', 'category' => 'DTH', 'sp_key' => '53', 'type' => 'dth'],
            ['name' => 'Tata Play', 'category' => 'DTH', 'sp_key' => '55', 'type' => 'dth'],
            ['name' => 'Videocon D2H', 'category' => 'DTH', 'sp_key' => '56', 'type' => 'dth'],
        ];
    }

    public function getRechargeOperatorsUrl(): string
    {
        return RechargeOperators::getUrl();
    }

    public function getInspayOperatorsUrl(): string
    {
        return InspayOperators::getUrl();
    }
}
