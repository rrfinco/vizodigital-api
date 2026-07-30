<?php

namespace App\Filament\Resources\Deposits\Pages;

use App\Filament\Resources\Deposits\DepositResource;
use App\Models\Deposit;
use App\Services\Payment\PaymentService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewDeposit extends ViewRecord
{
    protected static string $resource = DepositResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve & credit wallet')
                ->color('success')
                ->icon('heroicon-o-check-badge')
                ->requiresConfirmation()
                ->modalDescription('This will credit the deposit amount to the user wallet.')
                ->visible(fn (): bool => $this->canReview())
                ->form([
                    Textarea::make('notes')
                        ->label('Admin notes (optional)')
                        ->rows(2),
                ])
                ->action(function (array $data, PaymentService $paymentService): void {
                    /** @var Deposit $record */
                    $record = $this->record;

                    try {
                        $paymentService->approveBankTransfer(
                            $record,
                            auth()->user(),
                            $data['notes'] ?? null
                        );
                        $this->record->refresh();

                        Notification::make()
                            ->title('Deposit approved')
                            ->body('Wallet has been credited.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Approval failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn (): bool => $this->canReview())
                ->form([
                    Textarea::make('reason')
                        ->label('Rejection reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data, PaymentService $paymentService): void {
                    /** @var Deposit $record */
                    $record = $this->record;

                    try {
                        $paymentService->rejectBankTransfer(
                            $record,
                            auth()->user(),
                            $data['reason']
                        );
                        $this->record->refresh();

                        Notification::make()
                            ->title('Deposit rejected')
                            ->warning()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Rejection failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    private function canReview(): bool
    {
        /** @var Deposit $record */
        $record = $this->record;

        return $record->isBankTransfer()
            && $record->isPending()
            && (auth()->user()?->can('deposits.manage') ?? false);
    }
}
