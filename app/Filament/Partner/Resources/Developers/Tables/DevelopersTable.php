<?php

namespace App\Filament\Partner\Resources\Developers\Tables;

use App\Enums\OnboardingStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DevelopersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('company_name')->toggleable()->placeholder('—'),
                TextColumn::make('wallet_balance')
                    ->label('Wallet')
                    ->money('INR')
                    ->sortable(),
                TextColumn::make('onboarding_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state?->color())
                    ->formatStateUsing(fn ($state) => $state?->label()),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('onboarding_status')
                    ->options(collect(OnboardingStatus::cases())->mapWithKeys(
                        fn (OnboardingStatus $status): array => [$status->value => $status->label()]
                    )->all()),
            ])
            ->recordActions([]);
    }
}
