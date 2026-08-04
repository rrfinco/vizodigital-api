<?php

namespace App\Filament\Partner\Widgets;

use App\Models\WhitelabelWalletTransaction;
use Filament\Widgets\Widget;

class PartnerRecentFloatActivity extends Widget
{
    protected string $view = 'filament.partner.widgets.partner-recent-float-activity';

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected static ?int $sort = 10;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $wlId = auth()->user()?->whitelabel_id;

        $transactions = $wlId
            ? WhitelabelWalletTransaction::query()
                ->where('whitelabel_id', $wlId)
                ->latest()
                ->take(8)
                ->get()
            : collect();

        return [
            'transactions' => $transactions,
        ];
    }
}
