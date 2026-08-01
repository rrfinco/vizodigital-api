<?php

namespace App\Filament\User\Pages;

use App\Models\Deposit;
use App\Services\Payment\PaymentService;
use App\Services\Portal\PortalSettings;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use UnitEnum;

class Wallet extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static string|UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Wallet';

    protected static ?string $title = 'My Wallet';

    protected string $view = 'filament.user.pages.wallet';

    public ?float $amount = null;

    public string $paymentMethod = 'online';

    public string $utr = '';

    /** @var TemporaryUploadedFile|null */
    public $proof = null;

    public function mount(PortalSettings $settings): void
    {
        $this->amount = null;
        $this->utr = '';
        $this->proof = null;

        $online = $settings->walletOnlineEnabled();
        $bank = $settings->walletBankTransferEnabled();

        if ($online) {
            $this->paymentMethod = 'online';
        } elseif ($bank) {
            $this->paymentMethod = 'bank_transfer';
        } else {
            $this->paymentMethod = 'online';
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, Deposit>
     */
    public function getRecentDeposits(): \Illuminate\Support\Collection
    {
        return Deposit::where('user_id', auth()->id())
            ->latest()
            ->take(5)
            ->get();
    }

    /**
     * Unified wallet activity: deposits, debits/credits, and commission earnings.
     *
     * @return \Illuminate\Support\Collection<int, array{
     *   id: string,
     *   category: string,
     *   title: string,
     *   description: string,
     *   amount: float,
     *   is_credit: bool,
     *   is_earning: bool,
     *   status: string,
     *   status_color: string,
     *   balance_after: ?float,
     *   created_at: \Illuminate\Support\Carbon
     * }>
     */
    public function getRecentTransactions(): \Illuminate\Support\Collection
    {
        $userId = auth()->id();
        if (! $userId) {
            return collect();
        }

        $walletTx = \App\Models\WalletTransaction::query()
            ->where('user_id', $userId)
            ->latest()
            ->take(20)
            ->get()
            ->map(function (\App\Models\WalletTransaction $tx): array {
                $isCredit = $tx->type === 'credit';
                $description = (string) ($tx->description ?: '');
                $isEarning = $isCredit && str_contains(mb_strtolower($description), 'commission');

                $title = match (true) {
                    $isEarning => 'Commission Earning',
                    $isCredit && str_contains(mb_strtolower($description), 'reversal') => 'Refund / Reversal',
                    $isCredit => 'Wallet Credit',
                    default => 'Wallet Debit',
                };

                return [
                    'id' => 'tx-'.$tx->id,
                    'category' => $isEarning ? 'earning' : $tx->type,
                    'title' => $title,
                    'description' => $description !== ''
                        ? $description
                        : ($isCredit ? 'Funds added to wallet' : 'Wallet charge'),
                    'amount' => abs((float) $tx->amount),
                    'is_credit' => $isCredit,
                    'is_earning' => $isEarning,
                    'status' => 'Success',
                    'status_color' => 'success',
                    'balance_after' => (float) $tx->balance_after,
                    'created_at' => $tx->created_at ?? now(),
                ];
            });

        $deposits = Deposit::query()
            ->where('user_id', $userId)
            ->latest()
            ->take(10)
            ->get()
            ->map(function (Deposit $deposit): array {
                $methodLabel = $deposit->method === Deposit::METHOD_BANK_TRANSFER ? 'Bank transfer' : 'Online';
                $statusColor = match ($deposit->status) {
                    'success', 'approved' => 'success',
                    'pending' => 'warning',
                    default => 'danger',
                };

                $ref = $deposit->utr
                    ? 'UTR '.$deposit->utr
                    : ($deposit->gateway_ref ? 'Ref '.$deposit->gateway_ref : $deposit->order_id);

                return [
                    'id' => 'dep-'.$deposit->id,
                    'category' => 'deposit',
                    'title' => 'Deposit · '.$methodLabel,
                    'description' => $ref,
                    'amount' => abs((float) $deposit->amount),
                    'is_credit' => true,
                    'is_earning' => false,
                    'status' => strtoupper((string) $deposit->status),
                    'status_color' => $statusColor,
                    'balance_after' => null,
                    'created_at' => $deposit->created_at ?? now(),
                ];
            });

        return $walletTx
            ->concat($deposits)
            ->sortByDesc(fn (array $row) => $row['created_at']->getTimestamp())
            ->take(15)
            ->values();
    }

    /**
     * @return array{account_name: string, account_number: string, ifsc: string, bank_name: string, upi_id: string}
     */
    public function getBankDetails(): array
    {
        return app(PortalSettings::class)->bankTransferDetails();
    }

    public function isOnlineEnabled(): bool
    {
        return app(PortalSettings::class)->walletOnlineEnabled();
    }

    public function isBankTransferEnabled(): bool
    {
        return app(PortalSettings::class)->walletBankTransferEnabled();
    }

    public function submitPayment(PaymentService $paymentService)
    {
        if (! $this->isOnlineEnabled()) {
            Notification::make()
                ->title('Online payment disabled')
                ->body('Online payment is currently unavailable.')
                ->danger()
                ->send();

            return;
        }

        $this->validate([
            'amount' => 'required|numeric|min:1|max:500000',
        ]);

        try {
            $user = auth()->user();
            $redirectUrl = $paymentService->initiatePayment($user, $this->amount);

            return redirect()->away($redirectUrl);
        } catch (\Throwable $e) {
            Log::error('Failed initiating payment from user panel: '.$e->getMessage());

            Notification::make()
                ->title('Payment Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function submitBankTransfer(PaymentService $paymentService): void
    {
        if (! $this->isBankTransferEnabled()) {
            Notification::make()
                ->title('Bank transfer disabled')
                ->body('Bank transfer is currently unavailable.')
                ->danger()
                ->send();

            return;
        }

        $this->validate([
            'amount' => 'required|numeric|min:1|max:500000',
            'utr' => 'required|string|min:6|max:64',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        try {
            $proofPath = null;

            if ($this->proof instanceof TemporaryUploadedFile) {
                $proofPath = $this->proof->store('deposit-proofs', 'public');
            }

            $paymentService->initiateBankTransfer(
                auth()->user(),
                (float) $this->amount,
                $this->utr,
                $proofPath
            );

            $this->reset(['amount', 'utr', 'proof']);

            Notification::make()
                ->title('Request submitted')
                ->body('Your bank transfer request is pending admin approval. Wallet will be credited after verification.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            if (isset($proofPath) && $proofPath) {
                Storage::disk('public')->delete($proofPath);
            }

            Log::error('Failed submitting bank transfer: '.$e->getMessage());

            Notification::make()
                ->title('Submission failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
