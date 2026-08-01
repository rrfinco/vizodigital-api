<?php

namespace App\Filament\Concerns;

use App\Filament\Pages\ManageOperatorCommissions;
use App\Models\UserOperatorCommission;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

trait InteractsWithRechargeOperators
{
    public string $typeFilter = '';

    public string $search = '';

    public int $filterVersion = 0;

    public function updatedTypeFilter(): void
    {
        $this->typeFilter = trim($this->typeFilter);
    }

    public function updatedSearch(): void
    {
        // live search only
    }

    public function clearFilters(): void
    {
        $this->reset(['typeFilter', 'search']);
        $this->typeFilter = '';
        $this->search = '';
        $this->filterVersion++;
    }

    public function hasActiveFilters(): bool
    {
        return trim($this->typeFilter) !== '' || trim($this->search) !== '';
    }

    public function copySpKey(int|string $spKey): void
    {
        Notification::make()
            ->title('Copied')
            ->body("operator_sp_key {$spKey} copied to clipboard.")
            ->success()
            ->send();
    }

    public function operatorLogoSrc(array $operator): string
    {
        $domain = $operator['web_logo_domain'] ?? null;
        if (! is_string($domain) || $domain === '') {
            return asset($operator['logo_path'] ?? '');
        }

        return 'https://www.google.com/s2/favicons?sz=128&domain='.rawurlencode(trim($domain));
    }

    /**
     * @return Collection<int, array{
     *   id: int,
     *   operator_name: string,
     *   type: string,
     *   sp_key: int,
     *   logo_path: string,
     *   web_logo_domain: string,
     *   default_commission: string,
     *   default_status: string,
     *   status: string,
     *   commission_percentage: string
     * }>
     */
    public function operators(): Collection
    {
        $userId = auth()->id();

        $configs = $userId
            ? UserOperatorCommission::query()
                ->where('user_id', $userId)
                ->get()
                ->keyBy(fn (UserOperatorCommission $row): string => $row->operator_type.'_'.$row->operator_sp_key)
            : collect();

        $rows = collect(ManageOperatorCommissions::OPERATORS)->map(function (array $operator) use ($configs) {
            $key = $operator['type'].'_'.$operator['sp_key'];
            $found = $configs->get($key);

            $status = $found
                ? ((bool) $found->status ? 'Active' : 'Inactive')
                : ($operator['default_status'] ?? 'Active');

            $commission = $found
                ? number_format((float) $found->commission_percentage, 2, '.', '')
                : ($operator['default_commission'] ?? '0.00');

            return array_merge($operator, [
                'status' => $status,
                'commission_percentage' => $commission,
            ]);
        });

        $type = trim($this->typeFilter);
        $search = trim($this->search);

        if ($type !== '') {
            $rows = $rows->where('type', $type)->values();
        }

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $rows = $rows
                ->filter(function (array $row) use ($needle): bool {
                    return str_contains(mb_strtolower($row['operator_name']), $needle)
                        || str_contains((string) $row['sp_key'], $needle)
                        || str_contains(mb_strtolower($row['type']), $needle);
                })
                ->values();
        }

        return $rows->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function mobileOperators(): Collection
    {
        return $this->operators()->where('type', 'mobile')->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function dthOperators(): Collection
    {
        return $this->operators()->where('type', 'dth')->values();
    }
}
