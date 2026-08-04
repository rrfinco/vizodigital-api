<?php

namespace App\Filament\Resources\Whitelabels\Tables;

use App\Enums\WhitelabelStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WhitelabelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->fontFamily('mono')
                    ->toggleable(),
                TextColumn::make('owner.email')
                    ->label('Owner')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('domains_count')
                    ->counts('domains')
                    ->label('Domains'),
                TextColumn::make('wallet_balance')
                    ->label('Wallet')
                    ->money('INR')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (WhitelabelStatus|string|null $state): string => match ($state instanceof WhitelabelStatus ? $state : WhitelabelStatus::tryFrom((string) $state)) {
                        WhitelabelStatus::Active => 'success',
                        WhitelabelStatus::Suspended => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (WhitelabelStatus|string|null $state): string => $state instanceof WhitelabelStatus
                        ? $state->label()
                        : (WhitelabelStatus::tryFrom((string) $state)?->label() ?? (string) $state)),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('status')
                    ->options(WhitelabelStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
