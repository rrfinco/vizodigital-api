<?php

namespace App\Filament\Resources\Whitelabels\Pages;

use App\Filament\Resources\Whitelabels\WhitelabelResource;
use App\Models\Whitelabel;
use App\Services\Whitelabel\WhitelabelFloatService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditWhitelabel extends EditRecord
{
    protected static string $resource = WhitelabelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('adjustFloat')
                ->label('Adjust float')
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->visible(fn (): bool => auth()->user()?->can('whitelabel-float.manage') ?? false)
                ->form([
                    TextInput::make('amount')
                        ->label('Amount (+ credit / − debit)')
                        ->numeric()
                        ->required()
                        ->helperText('Positive credits float, negative debits float.'),
                    Textarea::make('reason')
                        ->label('Reason')
                        ->required()
                        ->rows(2),
                ])
                ->action(function (array $data, WhitelabelFloatService $floatService): void {
                    /** @var Whitelabel $record */
                    $record = $this->record;

                    try {
                        $floatService->adjustFloat(
                            $record,
                            auth()->user(),
                            (float) $data['amount'],
                            (string) $data['reason']
                        );
                        $this->record->refresh();

                        Notification::make()
                            ->title('Float updated')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Float adjust failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            DeleteAction::make(),
        ];
    }
}
