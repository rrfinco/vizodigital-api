<?php

namespace App\Filament\Resources\WhitelabelFloatRequests\Pages;

use App\Filament\Resources\WhitelabelFloatRequests\WhitelabelFloatRequestResource;
use App\Models\WhitelabelFloatRequest;
use App\Services\Whitelabel\WhitelabelFloatService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewWhitelabelFloatRequest extends ViewRecord
{
    protected static string $resource = WhitelabelFloatRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve & credit float')
                ->color('success')
                ->icon('heroicon-o-check-badge')
                ->requiresConfirmation()
                ->modalDescription('This will credit the amount to the white-label float wallet.')
                ->visible(fn (): bool => $this->canReview())
                ->form([
                    Textarea::make('notes')
                        ->label('Admin notes (optional)')
                        ->rows(2),
                ])
                ->action(function (array $data, WhitelabelFloatService $floatService): void {
                    /** @var WhitelabelFloatRequest $record */
                    $record = $this->record;

                    try {
                        $floatService->approve($record, auth()->user(), $data['notes'] ?? null);
                        $this->record->refresh();

                        Notification::make()
                            ->title('Float request approved')
                            ->body('White-label float has been credited.')
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
                ->action(function (array $data, WhitelabelFloatService $floatService): void {
                    /** @var WhitelabelFloatRequest $record */
                    $record = $this->record;

                    try {
                        $floatService->reject($record, auth()->user(), $data['reason']);
                        $this->record->refresh();

                        Notification::make()
                            ->title('Float request rejected')
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
        /** @var WhitelabelFloatRequest $record */
        $record = $this->record;

        return $record->isPending()
            && (auth()->user()?->can('whitelabel-float.manage') ?? false);
    }
}
