<?php

namespace App\Filament\Resources\DeveloperWallets\Tables;

use App\Enums\OnboardingStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DeveloperWalletsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Developer')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): ?string => $record->company_name ?: null),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('wallet_balance')
                    ->label('Main wallet')
                    ->money('INR')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('earning_balance')
                    ->label('Earning wallet')
                    ->money('INR')
                    ->sortable()
                    ->alignEnd()
                    ->color(fn ($state): string => (float) $state > 0 ? 'success' : 'gray'),
                TextColumn::make('onboarding_status')
                    ->label('KYC')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof OnboardingStatus
                        ? $state->label()
                        : (string) $state)
                    ->color(fn ($state): string => $state instanceof OnboardingStatus
                        ? $state->color()
                        : 'gray')
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('earning_balance', 'desc')
            ->filters([
                SelectFilter::make('onboarding_status')
                    ->label('KYC status')
                    ->options(collect(OnboardingStatus::cases())
                        ->mapWithKeys(fn (OnboardingStatus $status) => [$status->value => $status->label()])
                        ->all()),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
