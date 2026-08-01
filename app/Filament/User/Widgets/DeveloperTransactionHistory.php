<?php

namespace App\Filament\User\Widgets;

use App\Models\Deposit;
use App\Models\WalletTransaction;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class DeveloperTransactionHistory extends Widget
{
    protected string $view = 'filament.user.widgets.developer-transaction-history';

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 2,
    ];

    protected static ?int $sort = 2;

    /**
     * @return Collection<int, array{
     *   id: string,
     *   type: string,
     *   title: string,
     *   description: string,
     *   amount: float,
     *   is_credit: bool,
     *   status: string,
     *   status_color: string,
     *   balance_after: ?float,
     *   date: string,
     *   raw_date: \Illuminate\Support\Carbon
     * }>
     */
    public function getTransactions(): Collection
    {
        $userId = auth()->id();
        if (! $userId) {
            return collect();
        }

        $walletTx = WalletTransaction::query()
            ->where('user_id', $userId)
            ->latest()
            ->take(10)
            ->get()
            ->map(function (WalletTransaction $tx): array {
                $isCredit = $tx->type === 'credit';
                $description = (string) ($tx->description ?: '');
                $isEarning = $isCredit && str_contains(mb_strtolower($description), 'commission');

                return [
                    'id' => 'tx-'.$tx->id,
                    'type' => $tx->type,
                    'title' => $isEarning
                        ? 'Commission Earning'
                        : ($isCredit ? 'Wallet Credit' : 'Wallet Debit'),
                    'description' => $description !== ''
                        ? $description
                        : ($isCredit ? 'Funds added to wallet' : 'Wallet charge'),
                    'amount' => abs((float) $tx->amount),
                    'is_credit' => $isCredit,
                    'status' => $isEarning ? 'Earning' : 'Success',
                    'status_color' => 'success',
                    'balance_after' => (float) $tx->balance_after,
                    'date' => $tx->created_at?->format('d M Y, h:i A') ?? '',
                    'raw_date' => $tx->created_at ?? now(),
                ];
            });

        $deposits = Deposit::query()
            ->where('user_id', $userId)
            ->latest()
            ->take(5)
            ->get()
            ->map(function (Deposit $dep): array {
                $statusLabel = ucfirst($dep->status);
                $statusColor = match ($dep->status) {
                    'approved' => 'success',
                    'pending' => 'warning',
                    'rejected' => 'danger',
                    default => 'gray',
                };

                return [
                    'id' => 'dep-'.$dep->id,
                    'type' => 'deposit',
                    'title' => 'Deposit Request',
                    'description' => 'UTR: '.($dep->utr ?: 'N/A').' ('.strtoupper($dep->payment_method).')',
                    'amount' => (float) $dep->amount,
                    'is_credit' => true,
                    'status' => $statusLabel,
                    'status_color' => $statusColor,
                    'balance_after' => null,
                    'date' => $dep->created_at?->format('d M Y, h:i A') ?? '',
                    'raw_date' => $dep->created_at ?? now(),
                ];
            });

        return $walletTx->concat($deposits)
            ->sortByDesc('raw_date')
            ->take(8)
            ->values();
    }
}
