<?php

namespace App\Filament\User\Widgets;

use App\Filament\Pages\ManageOperatorCommissions;
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
     * Subset of ManageOperatorCommissions::OPERATORS shown on the dashboard.
     * Use operator ids (not hardcoded SP keys) so this list cannot drift.
     *
     * @var list<int>
     */
    private const POPULAR_OPERATOR_IDS = [1, 2, 3, 5, 7, 8, 10, 11];

    /**
     * @return list<array{name: string, category: string, sp_key: string, type: string}>
     */
    public function getPopularShortcodes(): array
    {
        $byId = collect(ManageOperatorCommissions::OPERATORS)->keyBy('id');

        return collect(self::POPULAR_OPERATOR_IDS)
            ->map(function (int $id) use ($byId): ?array {
                $operator = $byId->get($id);
                if (! is_array($operator)) {
                    return null;
                }

                $type = strtolower((string) $operator['type']);

                return [
                    'name' => (string) $operator['operator_name'],
                    'category' => $type === 'dth' ? 'DTH' : 'Mobile',
                    'sp_key' => (string) $operator['sp_key'],
                    'type' => $type,
                ];
            })
            ->filter()
            ->values()
            ->all();
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
