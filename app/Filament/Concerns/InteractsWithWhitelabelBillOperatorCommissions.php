<?php

namespace App\Filament\Concerns;

use App\Models\Whitelabel;
use App\Models\WhitelabelBillOperatorCommission;
use App\Services\Inspay\InspayOperatorCatalog;
use Filament\Notifications\Notification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

trait InteractsWithWhitelabelBillOperatorCommissions
{
    public string $category = '';

    public string $search = '';

    public int $filterVersion = 0;

    public ?int $selectedWhitelabelId = null;

    /**
     * @var array<string, array{commission_type: string, commission_value: string, status: string}>
     */
    public array $commissionRows = [];

    public function updatedCategory(): void
    {
        $this->category = trim($this->category);
        $this->resetPage();
        $this->hydrateCommissionRows();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->hydrateCommissionRows();
    }

    public function updatedSelectedWhitelabelId(): void
    {
        $this->commissionRows = [];
        $this->hydrateCommissionRows();
    }

    public function updatedPaginators($page, $pageName): void
    {
        $this->hydrateCommissionRows();
    }

    public function clearFilters(): void
    {
        $this->category = '';
        $this->search = '';
        $this->filterVersion++;
        $this->resetPage();
        $this->hydrateCommissionRows();
    }

    public function hasActiveFilters(): bool
    {
        return trim($this->category) !== '' || trim($this->search) !== '';
    }

    public function copyCode(string $code): void
    {
        Notification::make()
            ->title('Copied')
            ->body("Operator code {$code} copied to clipboard.")
            ->success()
            ->send();
    }

    /**
     * @return list<string>
     */
    public function categories(): array
    {
        return app(InspayOperatorCatalog::class)->categories();
    }

    /**
     * @return array<string, int>
     */
    public function categoryCounts(): array
    {
        return app(InspayOperatorCatalog::class)
            ->all()
            ->groupBy('category')
            ->map->count()
            ->sortKeys()
            ->all();
    }

    public function totalCount(): int
    {
        return app(InspayOperatorCatalog::class)->all()->count();
    }

    public function filteredCount(): int
    {
        return app(InspayOperatorCatalog::class)->search(
            category: $this->category !== '' ? $this->category : null,
            query: $this->search !== '' ? $this->search : null,
        )->count();
    }

    /**
     * @return LengthAwarePaginator<int, array{
     *   category: string,
     *   name: string,
     *   code: string,
     *   commission_type: string,
     *   commission_value: string,
     *   status: string
     * }>
     */
    public function operators(): LengthAwarePaginator
    {
        $filtered = app(InspayOperatorCatalog::class)->search(
            category: $this->category !== '' ? $this->category : null,
            query: $this->search !== '' ? $this->search : null,
        );

        $perPage = 100;
        $page = $this->getPage();
        $slice = $filtered->forPage($page, $perPage)->values();
        $enriched = $this->enrichOperatorsWithCommission($slice);

        return new LengthAwarePaginator(
            $enriched,
            $filtered->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * @return Collection<int, array{id: int, label: string}>
     */
    public function whitelabelsForSelect(): Collection
    {
        return Whitelabel::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Whitelabel $wl) => ['id' => $wl->id, 'label' => $wl->name.' ('.$wl->slug.')'])
            ->values();
    }

    public function hydrateCommissionRows(): void
    {
        if (! $this->selectedWhitelabelId) {
            $this->commissionRows = [];

            return;
        }

        $paginator = $this->operatorsWithoutCommissionEnrichment();
        $codes = $paginator->getCollection()->pluck('code')->all();

        if ($codes === []) {
            $this->commissionRows = [];

            return;
        }

        $existing = WhitelabelBillOperatorCommission::query()
            ->where('whitelabel_id', $this->selectedWhitelabelId)
            ->whereIn('opcode', $codes)
            ->get()
            ->keyBy('opcode');

        $rows = [];
        foreach ($codes as $code) {
            $found = $existing->get($code);
            $rows[$code] = [
                'commission_type' => $found?->commission_type ?? WhitelabelBillOperatorCommission::TYPE_PERCENTAGE,
                'commission_value' => $found
                    ? number_format((float) $found->commission_value, 2, '.', '')
                    : '0.00',
                'status' => $found
                    ? ((bool) $found->status ? 'Active' : 'Inactive')
                    : 'Active',
            ];
        }

        $this->commissionRows = $rows;
    }

    public function saveCommissions(): void
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

        $codes = array_keys($this->commissionRows);
        if ($codes === []) {
            Notification::make()
                ->title('Nothing to save')
                ->warning()
                ->send();

            return;
        }

        foreach ($this->commissionRows as $opcode => $row) {
            $type = (string) ($row['commission_type'] ?? WhitelabelBillOperatorCommission::TYPE_PERCENTAGE);
            $value = (string) ($row['commission_value'] ?? '0');
            $status = (string) ($row['status'] ?? 'Active');

            if (! in_array($type, [WhitelabelBillOperatorCommission::TYPE_PERCENTAGE, WhitelabelBillOperatorCommission::TYPE_FLAT], true)) {
                throw ValidationException::withMessages([
                    "commissionRows.{$opcode}.commission_type" => "Invalid commission type for opcode {$opcode}.",
                ]);
            }

            if (! is_numeric($value)) {
                throw ValidationException::withMessages([
                    "commissionRows.{$opcode}.commission_value" => "Commission must be numeric for opcode {$opcode}.",
                ]);
            }

            $valueFloat = (float) $value;
            if ($valueFloat < 0) {
                throw ValidationException::withMessages([
                    "commissionRows.{$opcode}.commission_value" => "Commission cannot be negative for opcode {$opcode}.",
                ]);
            }

            if ($type === WhitelabelBillOperatorCommission::TYPE_PERCENTAGE && $valueFloat > 100) {
                throw ValidationException::withMessages([
                    "commissionRows.{$opcode}.commission_value" => "Percentage commission must be between 0 and 100 for opcode {$opcode}.",
                ]);
            }

            WhitelabelBillOperatorCommission::query()->updateOrCreate(
                [
                    'whitelabel_id' => $whitelabel->id,
                    'opcode' => (string) $opcode,
                ],
                [
                    'commission_type' => $type,
                    'commission_value' => $valueFloat,
                    'status' => $status === 'Active',
                ],
            );
        }

        Notification::make()
            ->title('White-label bill commissions saved')
            ->body('Saved '.count($codes).' operator(s) on this page.')
            ->success()
            ->send();
    }

    /**
     * @param  Collection<int, array{category: string, name: string, code: string}>  $slice
     * @return Collection<int, array{
     *   category: string,
     *   name: string,
     *   code: string,
     *   commission_type: string,
     *   commission_value: string,
     *   status: string
     * }>
     */
    protected function enrichOperatorsWithCommission(Collection $slice): Collection
    {
        if (! $this->selectedWhitelabelId) {
            return $slice->map(fn (array $op) => array_merge($op, [
                'commission_type' => WhitelabelBillOperatorCommission::TYPE_PERCENTAGE,
                'commission_value' => '0.00',
                'status' => 'Active',
            ]));
        }

        $codes = $slice->pluck('code')->all();
        $existing = WhitelabelBillOperatorCommission::query()
            ->where('whitelabel_id', $this->selectedWhitelabelId)
            ->whereIn('opcode', $codes)
            ->get()
            ->keyBy('opcode');

        return $slice->map(function (array $op) use ($existing) {
            $code = $op['code'];

            if (isset($this->commissionRows[$code])) {
                $row = $this->commissionRows[$code];

                return array_merge($op, [
                    'commission_type' => $row['commission_type'] ?? WhitelabelBillOperatorCommission::TYPE_PERCENTAGE,
                    'commission_value' => $row['commission_value'] ?? '0.00',
                    'status' => $row['status'] ?? 'Active',
                ]);
            }

            $found = $existing->get($code);

            return array_merge($op, [
                'commission_type' => $found?->commission_type ?? WhitelabelBillOperatorCommission::TYPE_PERCENTAGE,
                'commission_value' => $found
                    ? number_format((float) $found->commission_value, 2, '.', '')
                    : '0.00',
                'status' => $found
                    ? ((bool) $found->status ? 'Active' : 'Inactive')
                    : 'Active',
            ]);
        });
    }

    /**
     * @return LengthAwarePaginator<int, array{category: string, name: string, code: string}>
     */
    protected function operatorsWithoutCommissionEnrichment(): LengthAwarePaginator
    {
        $filtered = app(InspayOperatorCatalog::class)->search(
            category: $this->category !== '' ? $this->category : null,
            query: $this->search !== '' ? $this->search : null,
        );

        $perPage = 100;
        $page = $this->getPage();

        return new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }
}
