<?php

namespace App\Filament\Resources\KycApplications\Tables;

use App\Enums\OnboardingStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KycApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company_name')
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('onboarding_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state?->color())
                    ->formatStateUsing(fn ($state) => $state?->label()),
                TextColumn::make('kyc_documents_count')
                    ->counts('kycDocuments')
                    ->label('Docs'),
                TextColumn::make('kyc_submitted_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('kyc_submitted_at', 'desc')
            ->filters([
                SelectFilter::make('onboarding_status')
                    ->options(collect(OnboardingStatus::cases())->mapWithKeys(
                        fn (OnboardingStatus $status): array => [$status->value => $status->label()]
                    )->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
