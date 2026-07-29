<?php

namespace App\Filament\Resources\ApiCredentials\Tables;

use App\Enums\CredentialStatus;
use App\Enums\EnvironmentSlug;
use App\Models\ApiCredential;
use App\Services\Credentials\CredentialProvisioner;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApiCredentialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Developer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('environment.label')
                    ->label('Environment')
                    ->badge()
                    ->sortable(),
                TextColumn::make('client_id')
                    ->label('Client ID')
                    ->searchable()
                    ->copyable()
                    ->limit(24),
                TextColumn::make('merchant_id')
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => $state?->color()),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                Action::make('unlockProduction')
                    ->label('Unlock live')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Unlock production credentials')
                    ->modalDescription('Generates (or activates) live API keys for this developer.')
                    ->visible(function (ApiCredential $record): bool {
                        $slug = $record->environment?->slug;

                        return $slug === EnvironmentSlug::Production
                            && $record->status !== CredentialStatus::Active;
                    })
                    ->action(function (ApiCredential $record, CredentialProvisioner $provisioner): void {
                        $provisioner->unlockProduction($record->user);
                        Notification::make()
                            ->title('Production credentials unlocked')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
