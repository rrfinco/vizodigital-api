<?php

namespace App\Filament\Resources\Deposits\Tables;

use App\Models\Deposit;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DepositsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('method')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Deposit::METHOD_BANK_TRANSFER => 'Bank',
                        default => 'Online',
                    })
                    ->color(fn (string $state): string => $state === Deposit::METHOD_BANK_TRANSFER ? 'warning' : 'info'),
                TextColumn::make('amount')
                    ->money('INR')
                    ->sortable(),
                TextColumn::make('utr')
                    ->label('UTR')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                TextColumn::make('order_id')
                    ->label('Order')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('method')
                    ->options([
                        Deposit::METHOD_ONLINE => 'Online',
                        Deposit::METHOD_BANK_TRANSFER => 'Bank transfer',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'failed' => 'Failed',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
