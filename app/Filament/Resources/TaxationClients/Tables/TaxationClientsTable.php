<?php

namespace App\Filament\Resources\TaxationClients\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TaxationClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('first_name')->searchable(),
                TextColumn::make('last_name')->searchable(),
                TextColumn::make('email')->searchable()->toggleable(),
                TextColumn::make('phone')->searchable(),
                TextColumn::make('pan')->searchable(),
                TextColumn::make('user.name')->label('Developer')->searchable(),
                TextColumn::make('whitelabel.name')->label('WL')->placeholder('B2C')->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('whitelabel_id')
                    ->label('White-label')
                    ->relationship('whitelabel', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
