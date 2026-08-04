<?php

namespace App\Filament\Resources\Whitelabels\Pages;

use App\Filament\Resources\Whitelabels\WhitelabelResource;
use App\Models\User;
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

    /** @var array{name: string, email: string, password?: string}|null */
    protected ?array $ownerPayload = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Whitelabel $record */
        $record = $this->record;
        $owner = $record->owner;

        $data['owner_name'] = $owner?->name;
        $data['owner_email'] = $owner?->email;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->ownerPayload = [
            'name' => (string) $data['owner_name'],
            'email' => (string) $data['owner_email'],
        ];

        if (filled($data['owner_password'] ?? null)) {
            $this->ownerPayload['password'] = (string) $data['owner_password'];
        }

        unset($data['owner_name'], $data['owner_email'], $data['owner_password']);

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->ownerPayload) {
            return;
        }

        /** @var Whitelabel $record */
        $record = $this->record;
        $owner = $record->owner;

        if (! $owner instanceof User) {
            return;
        }

        $updates = [
            'name' => $this->ownerPayload['name'],
            'email' => $this->ownerPayload['email'],
        ];

        if (isset($this->ownerPayload['password'])) {
            $updates['password'] = $this->ownerPayload['password'];
        }

        $owner->update($updates);
        $this->ownerPayload = null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('adjustFloat')
                ->label('Adjust wallet balance')
                ->modalHeading('Adjust wallet balance')
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->visible(fn (): bool => auth()->user()?->can('whitelabel-float.manage') ?? false)
                ->form([
                    TextInput::make('amount')
                        ->label('Amount (+ credit / − debit)')
                        ->numeric()
                        ->required()
                        ->helperText('Positive credits the wallet, negative debits the wallet.'),
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
                            ->title('Wallet balance updated')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Wallet balance adjust failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            DeleteAction::make(),
        ];
    }
}
