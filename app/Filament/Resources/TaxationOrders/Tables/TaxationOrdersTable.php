<?php

namespace App\Filament\Resources\TaxationOrders\Tables;

use App\Models\TaxationOrder;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TaxationOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('api_request_id')->searchable()->toggleable(),
                TextColumn::make('taxation_service_id')->label('Service'),
                TextColumn::make('service_name')->wrap()->limit(40),
                TextColumn::make('amount')->money('INR')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        TaxationOrder::STATUS_COMPLETED => 'success',
                        TaxationOrder::STATUS_PROCESSING => 'info',
                        TaxationOrder::STATUS_PENDING => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('documents_status')
                    ->label('Documents')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        TaxationOrder::DOCUMENTS_APPROVED => 'success',
                        TaxationOrder::DOCUMENTS_VERIFIED => 'info',
                        TaxationOrder::DOCUMENTS_SUBMITTED => 'warning',
                        TaxationOrder::DOCUMENTS_REJECTED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('user.name')->label('Developer')->searchable(),
                TextColumn::make('whitelabel.name')->label('WL')->placeholder('B2C')->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        TaxationOrder::STATUS_PENDING => 'Pending',
                        TaxationOrder::STATUS_PROCESSING => 'Processing',
                        TaxationOrder::STATUS_COMPLETED => 'Completed',
                        TaxationOrder::STATUS_CANCELLED => 'Cancelled',
                    ]),
                SelectFilter::make('documents_status')
                    ->label('Documents')
                    ->options([
                        TaxationOrder::DOCUMENTS_PENDING => 'Awaiting upload',
                        TaxationOrder::DOCUMENTS_SUBMITTED => 'Submitted',
                        TaxationOrder::DOCUMENTS_VERIFIED => 'Verified',
                        TaxationOrder::DOCUMENTS_APPROVED => 'Approved',
                        TaxationOrder::DOCUMENTS_REJECTED => 'Rejected',
                    ]),
                SelectFilter::make('whitelabel_id')
                    ->relationship('whitelabel', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
