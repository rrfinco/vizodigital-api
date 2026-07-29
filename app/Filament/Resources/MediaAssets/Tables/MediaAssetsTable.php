<?php

namespace App\Filament\Resources\MediaAssets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class MediaAssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('original_name')
                    ->label('File')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('mime_type')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('size')
                    ->formatStateUsing(fn (?int $state): string => $state ? number_format($state / 1024, 1).' KB' : '—')
                    ->sortable(),
                TextColumn::make('alt')
                    ->toggleable(),
                TextColumn::make('path')
                    ->label('URL')
                    ->formatStateUsing(function (?string $state, $record): string {
                        if (! $state) {
                            return '—';
                        }

                        return Storage::disk($record->disk ?: 'public')->url($state);
                    })
                    ->copyable()
                    ->limit(40),
                TextColumn::make('uploader.name')
                    ->label('Uploaded by')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
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
