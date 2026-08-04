<?php

namespace App\Filament\Partner\Pages;

use App\Models\Whitelabel;
use App\Models\WhitelabelFloatRequest;
use App\Models\WhitelabelWalletTransaction;
use App\Services\Whitelabel\WhitelabelFloatService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use UnitEnum;

class FloatWallet extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static string|UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Float wallet';

    protected static ?string $title = 'Float wallet';

    protected string $view = 'filament.partner.pages.float-wallet';

    public ?float $amount = null;

    public string $utr = '';

    /** @var TemporaryUploadedFile|null */
    public $proof = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('whitelabel-float.request') ?? false;
    }

    public function getWhitelabel(): ?Whitelabel
    {
        return auth()->user()?->whitelabel;
    }

    /**
     * @return \Illuminate\Support\Collection<int, WhitelabelFloatRequest>
     */
    public function getRecentRequests()
    {
        $wlId = auth()->user()?->whitelabel_id;
        if (! $wlId) {
            return collect();
        }

        return WhitelabelFloatRequest::query()
            ->where('whitelabel_id', $wlId)
            ->latest()
            ->take(10)
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, WhitelabelWalletTransaction>
     */
    public function getRecentLedger()
    {
        $wlId = auth()->user()?->whitelabel_id;
        if (! $wlId) {
            return collect();
        }

        return WhitelabelWalletTransaction::query()
            ->where('whitelabel_id', $wlId)
            ->latest()
            ->take(15)
            ->get();
    }

    public function submitFloatRequest(WhitelabelFloatService $floatService): void
    {
        $user = auth()->user();
        $whitelabel = $user?->whitelabel;

        if (! $user || ! $whitelabel) {
            Notification::make()
                ->title('White-label not found')
                ->danger()
                ->send();

            return;
        }

        $this->validate([
            'amount' => 'required|numeric|min:1|max:5000000',
            'utr' => 'required|string|min:6|max:64',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $proofPath = null;

        try {
            if ($this->proof instanceof TemporaryUploadedFile) {
                $proofPath = $this->proof->store('whitelabel-float-proofs', 'public');
            }

            $floatService->requestTopUp(
                $whitelabel,
                $user,
                (float) $this->amount,
                $this->utr,
                $proofPath
            );

            $this->reset(['amount', 'utr', 'proof']);

            Notification::make()
                ->title('Float request submitted')
                ->body('Platform admin will review and credit your float after verification.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            if ($proofPath) {
                Storage::disk('public')->delete($proofPath);
            }

            Log::error('Failed submitting whitelabel float request: '.$e->getMessage());

            Notification::make()
                ->title('Submission failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
