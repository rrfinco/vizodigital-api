<?php

namespace App\Filament\User\Pages;

use App\Filament\Pages\ManageOperatorCommissions;
use App\Models\RechargeTransaction;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;
use UnitEnum;

class Transactions extends Page
{
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Transactions';

    protected static ?string $title = 'Recharge transactions';

    protected static ?string $slug = 'transactions';

    protected string $view = 'filament.user.pages.transactions';

    /**
     * @return LengthAwarePaginator<int, RechargeTransaction>
     */
    public function getTransactionsProperty(): LengthAwarePaginator
    {
        return RechargeTransaction::query()
            ->where('user_id', auth()->id())
            ->latest('id')
            ->paginate(20);
    }

    public function operatorName(int $spKey, string $type): string
    {
        foreach (ManageOperatorCommissions::OPERATORS as $operator) {
            if ((int) $operator['sp_key'] === $spKey && $operator['type'] === $type) {
                return (string) $operator['operator_name'];
            }
        }

        return strtoupper($type).' · SP '.$spKey;
    }

    public function statusColor(string $status): string
    {
        return match (strtolower($status)) {
            'success' => 'success',
            'pending' => 'warning',
            'failed' => 'danger',
            default => 'gray',
        };
    }
}
