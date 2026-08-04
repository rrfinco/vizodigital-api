<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\InteractsWithWhitelabelBillOperatorCommissions;
use App\Models\Whitelabel;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\WithPagination;
use UnitEnum;

class ManageWhitelabelBillOperatorCommissions extends Page
{
    use InteractsWithWhitelabelBillOperatorCommissions;
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'White-label';

    protected static ?int $navigationSort = 31;

    protected static ?string $navigationLabel = 'WL bill commissions';

    protected static ?string $title = 'White-label bill payment commissions';

    protected static ?string $slug = 'whitelabel-bill-commissions';

    protected string $view = 'filament.admin.pages.manage-whitelabel-bill-operator-commissions';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('whitelabels.manage') ?? false;
    }

    public function mount(): void
    {
        $this->selectedWhitelabelId = Whitelabel::query()->orderBy('name')->value('id');
        $this->hydrateCommissionRows();
    }
}
