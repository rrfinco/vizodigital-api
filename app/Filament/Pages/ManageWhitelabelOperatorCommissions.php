<?php

namespace App\Filament\Pages;

use App\Filament\Pages\ManageOperatorCommissions;
use App\Models\Whitelabel;
use App\Models\WhitelabelOperatorCommission;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class ManageWhitelabelOperatorCommissions extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPercentBadge;

    protected static string|UnitEnum|null $navigationGroup = 'White-label';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'WL recharge commissions';

    protected static ?string $title = 'White-label recharge commissions';

    protected string $view = 'filament.admin.pages.manage-whitelabel-operator-commissions';

    public ?int $selectedWhitelabelId = null;

    /**
     * @var array<string, array{commission_percentage: string, status: string, operator_id: int}>
     */
    public array $rows = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('whitelabels.manage') ?? false;
    }

    public function mount(): void
    {
        $this->selectedWhitelabelId = $this->whitelabels()->first()?->id;
        $this->hydrateRows();
    }

    /**
     * @return Collection<int, Whitelabel>
     */
    private function whitelabels(): Collection
    {
        return Whitelabel::query()->orderBy('name')->get();
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
        $this->rows = [];
        $operators = ManageOperatorCommissions::OPERATORS;

        if (! $this->selectedWhitelabelId) {
            foreach ($operators as $operator) {
                $key = $this->operatorKey($operator);
                $this->rows[$key] = [
                    'operator_id' => $operator['id'],
                    'commission_percentage' => $operator['default_commission'],
                    'status' => $operator['default_status'],
                ];
            }

            return;
        }

        $existing = WhitelabelOperatorCommission::query()
            ->where('whitelabel_id', $this->selectedWhitelabelId)
            ->get()
            ->keyBy(fn (WhitelabelOperatorCommission $row): string => $row->operator_type.'_'.$row->operator_sp_key);

        foreach ($operators as $operator) {
            $key = $this->operatorKey($operator);
            $found = $existing->get($key);

            $this->rows[$key] = [
                'operator_id' => $operator['id'],
                'commission_percentage' => $found
                    ? number_format((float) $found->commission_percentage, 2, '.', '')
                    : $operator['default_commission'],
                'status' => $found
                    ? ((bool) $found->status ? 'Active' : 'Inactive')
                    : $operator['default_status'],
            ];
        }
    }

    public function updatedSelectedWhitelabelId(): void
    {
        $this->hydrateRows();
    }

    public function save(): void
    {
        if (! $this->selectedWhitelabelId) {
            throw ValidationException::withMessages([
                'selectedWhitelabelId' => 'Please select a white-label.',
            ]);
        }

        $whitelabel = Whitelabel::query()->find($this->selectedWhitelabelId);
        if (! $whitelabel) {
            throw ValidationException::withMessages([
                'selectedWhitelabelId' => 'Selected white-label not found.',
            ]);
        }

        foreach (ManageOperatorCommissions::OPERATORS as $operator) {
            $key = $this->operatorKey($operator);

            $commission = (string) ($this->rows[$key]['commission_percentage'] ?? $operator['default_commission']);
            $status = (string) ($this->rows[$key]['status'] ?? $operator['default_status']);

            if (! is_numeric($commission)) {
                throw ValidationException::withMessages([
                    'commission_percentage' => 'Commission must be numeric.',
                ]);
            }

            $commissionFloat = (float) $commission;
            if ($commissionFloat < 0 || $commissionFloat > 100) {
                throw ValidationException::withMessages([
                    'commission_percentage' => 'Commission must be between 0 and 100.',
                ]);
            }

            WhitelabelOperatorCommission::query()->updateOrCreate(
                [
                    'whitelabel_id' => $whitelabel->id,
                    'operator_type' => $operator['type'],
                    'operator_sp_key' => $operator['sp_key'],
                ],
                [
                    'commission_percentage' => $commissionFloat,
                    'status' => $status === 'Active',
                ],
            );
        }

        Notification::make()
            ->title('White-label commissions saved')
            ->success()
            ->send();
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

    public function whitelabelsForSelect(): Collection
    {
        return $this->whitelabels()
            ->map(fn (Whitelabel $wl) => ['id' => $wl->id, 'label' => $wl->name.' ('.$wl->slug.')'])
            ->values();
    }
}
