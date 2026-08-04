<?php

namespace App\Filament\Resources\WhitelabelFloatRequests\Tables;

use App\Models\WhitelabelFloatRequest;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WhitelabelFloatRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('whitelabel.name')
                    ->label('White-label')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('requester.email')
                    ->label('Requested by')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('amount')
                    ->money('INR')
                    ->sortable(),
                TextColumn::make('utr')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        WhitelabelFloatRequest::STATUS_PENDING => 'Pending',
                        WhitelabelFloatRequest::STATUS_APPROVED => 'Approved',
                        WhitelabelFloatRequest::STATUS_REJECTED => 'Rejected',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
