<?php

namespace App\Filament\Partner\Pages;

use App\Filament\Pages\ManagePlanApiAccess;
use App\Models\WhitelabelPlanApiAccess;
use App\Models\WhitelabelWalletTransaction;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MyPlanApiRates extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEye;

    protected static string|UnitEnum|null $navigationGroup = 'Commissions';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'My Plan API rates';

    protected static ?string $title = 'My Plan API rates';

    protected string $view = 'filament.partner.pages.my-plan-api-rates';

    /**
     * @var array<string, array{per_call_fee: string, status: string}>
     */
    public array $rows = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isWhitelabelPartner() ?? false;
    }

    public function mount(): void
    {
        $this->hydrateRows();
    }

    public function hydrateRows(): void
    {
        $wlId = auth()->user()?->whitelabel_id;
        $this->rows = [];

        $existing = $wlId
            ? WhitelabelPlanApiAccess::query()
                ->where('whitelabel_id', $wlId)
                ->get()
                ->keyBy('service')
            : collect();

        foreach (ManagePlanApiAccess::SERVICES as $service) {
            $found = $existing->get($service['key']);

            $this->rows[$service['key']] = [
                'per_call_fee' => $found
                    ? number_format((float) $found->per_call_fee, 2, '.', '')
                    : $service['default_fee'],
                'status' => $found && $found->isActive() ? 'Active' : 'Inactive',
            ];
        }
    }

    public function planApiMarginEarned(): float
    {
        $wlId = auth()->user()?->whitelabel_id;
        if (! $wlId) {
            return 0.0;
        }

        return (float) WhitelabelWalletTransaction::query()
            ->where('whitelabel_id', $wlId)
            ->where('type', 'credit')
            ->where('description', 'like', 'Plan API margin%')
            ->sum('amount');
    }

    /**
     * @return list<array{key: string, label: string, description: string, default_fee: string}>
     */
    public function services(): array
    {
        return ManagePlanApiAccess::SERVICES;
    }
}
