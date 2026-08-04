<?php

namespace App\Filament\Partner\Pages;

use App\Filament\Pages\ManageOperatorCommissions;
use App\Models\WhitelabelOperatorCommission;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MyRechargeRates extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEye;

    protected static string|UnitEnum|null $navigationGroup = 'Commissions';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'My recharge rates';

    protected static ?string $title = 'My recharge rates';

    protected string $view = 'filament.partner.pages.my-recharge-rates';

    /**
     * @var array<string, array{commission_percentage: string, status: string}>
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

    private function operatorKey(array $operator): string
    {
        return $operator['type'].'_'.$operator['sp_key'];
    }

    public function operatorLogoSrc(array $operator): string
    {
        $domain = $operator['web_logo_domain'] ?? null;
        if (! is_string($domain) || $domain === '') {
            return asset($operator['logo_path'] ?? '');
        }

        return 'https://www.google.com/s2/favicons?sz=128&domain='.rawurlencode(trim($domain));
    }

    public function hydrateRows(): void
    {
        $wlId = auth()->user()?->whitelabel_id;
        $this->rows = [];

        $existing = $wlId
            ? WhitelabelOperatorCommission::query()
                ->where('whitelabel_id', $wlId)
                ->get()
                ->keyBy(fn (WhitelabelOperatorCommission $row): string => $row->operator_type.'_'.$row->operator_sp_key)
            : collect();

        foreach (ManageOperatorCommissions::OPERATORS as $operator) {
            $key = $this->operatorKey($operator);
            $found = $existing->get($key);

            $this->rows[$key] = [
                'commission_percentage' => $found
                    ? number_format((float) $found->commission_percentage, 2, '.', '')
                    : $operator['default_commission'],
                'status' => $found
                    ? ((bool) $found->status ? 'Active' : 'Inactive')
                    : $operator['default_status'],
            ];
        }
    }

    /**
     * @return list<array{
     *   id: int,
     *   operator_name: string,
     *   type: string,
     *   sp_key: int,
     *   logo_path: string,
     *   web_logo_domain: string,
     *   default_commission: string,
     *   default_status: string
     * }>
     */
    public function operators(): array
    {
        return ManageOperatorCommissions::OPERATORS;
    }
}
