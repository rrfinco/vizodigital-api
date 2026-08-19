<?php

namespace App\Filament\Pages;

use App\Models\Whitelabel;
use App\Models\WhitelabelPlanApiAccess;
use App\Services\Taxation\TaxationCatalog;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class ManageWhitelabelTaxationAccess extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static string|UnitEnum|null $navigationGroup = 'White-label';

    protected static ?int $navigationSort = 34;

    protected static ?string $navigationLabel = 'WL taxation access';

    protected static ?string $title = 'White-label taxation API access';

    protected string $view = 'filament.admin.pages.manage-whitelabel-taxation-access';

    /**
     * @var array<string, array{status: string}>
     */
    public array $rows = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('whitelabels.manage') ?? false;
    }

    public function mount(): void
    {
        $this->hydrateRows();
    }

    /**
     * @return Collection<int, Whitelabel>
     */
    public function whitelabels(): Collection
    {
        return Whitelabel::query()->orderBy('name')->get();
    }

    public function hydrateRows(): void
    {
        $existing = WhitelabelPlanApiAccess::query()
            ->where('service', TaxationCatalog::SERVICE_ACCESS_KEY)
            ->get()
            ->keyBy('whitelabel_id');

        $this->rows = [];
        foreach ($this->whitelabels() as $wl) {
            $found = $existing->get($wl->id);
            $this->rows[(string) $wl->id] = [
                'status' => $found && $found->isActive() ? 'Active' : 'Inactive',
            ];
        }
    }

    public function save(): void
    {
        foreach ($this->whitelabels() as $wl) {
            $status = (string) ($this->rows[(string) $wl->id]['status'] ?? 'Inactive');

            WhitelabelPlanApiAccess::query()->updateOrCreate(
                [
                    'whitelabel_id' => $wl->id,
                    'service' => TaxationCatalog::SERVICE_ACCESS_KEY,
                ],
                [
                    'per_call_fee' => 0,
                    'status' => $status === 'Active',
                ],
            );
        }

        Notification::make()
            ->title('White-label taxation access saved')
            ->success()
            ->send();
    }
}
