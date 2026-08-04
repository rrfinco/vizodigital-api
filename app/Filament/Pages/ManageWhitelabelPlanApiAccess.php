<?php

namespace App\Filament\Pages;

use App\Models\Whitelabel;
use App\Models\WhitelabelPlanApiAccess;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class ManageWhitelabelPlanApiAccess extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static string|UnitEnum|null $navigationGroup = 'White-label';

    protected static ?int $navigationSort = 32;

    protected static ?string $navigationLabel = 'WL Plan API fees';

    protected static ?string $title = 'White-label Plan API fees';

    protected string $view = 'filament.admin.pages.manage-whitelabel-plan-api-access';

    public ?int $selectedWhitelabelId = null;

    /**
     * @var array<string, array{per_call_fee: string, status: string}>
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

    public function hydrateRows(): void
    {
        $this->rows = [];

        if (! $this->selectedWhitelabelId) {
            foreach (ManagePlanApiAccess::SERVICES as $service) {
                $this->rows[$service['key']] = [
                    'per_call_fee' => $service['default_fee'],
                    'status' => 'Inactive',
                ];
            }

            return;
        }

        $existing = WhitelabelPlanApiAccess::query()
            ->where('whitelabel_id', $this->selectedWhitelabelId)
            ->get()
            ->keyBy('service');

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

        foreach (ManagePlanApiAccess::SERVICES as $service) {
            $key = $service['key'];
            $fee = (string) ($this->rows[$key]['per_call_fee'] ?? $service['default_fee']);
            $status = (string) ($this->rows[$key]['status'] ?? 'Inactive');

            if (! is_numeric($fee)) {
                throw ValidationException::withMessages([
                    "rows.{$key}.per_call_fee" => "Per-call fee must be numeric for {$service['label']}.",
                ]);
            }

            $feeFloat = round((float) $fee, 2);
            if ($feeFloat < 0) {
                throw ValidationException::withMessages([
                    "rows.{$key}.per_call_fee" => "Per-call fee cannot be negative for {$service['label']}.",
                ]);
            }

            WhitelabelPlanApiAccess::query()->updateOrCreate(
                [
                    'whitelabel_id' => $whitelabel->id,
                    'service' => $key,
                ],
                [
                    'per_call_fee' => $feeFloat,
                    'status' => $status === 'Active',
                ],
            );
        }

        Notification::make()
            ->title('White-label Plan API fees saved')
            ->success()
            ->send();
    }

    /**
     * @return list<array{key: string, label: string, description: string, default_fee: string}>
     */
    public function services(): array
    {
        return ManagePlanApiAccess::SERVICES;
    }

    public function whitelabelsForSelect(): Collection
    {
        return $this->whitelabels()
            ->map(fn (Whitelabel $wl) => ['id' => $wl->id, 'label' => $wl->name.' ('.$wl->slug.')'])
            ->values();
    }
}
