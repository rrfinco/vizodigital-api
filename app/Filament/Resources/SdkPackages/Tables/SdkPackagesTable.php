<?php

namespace App\Filament\Resources\SdkPackages\Tables;

use App\Enums\SnippetLanguage;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SdkPackagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('language')
                    ->formatStateUsing(function (mixed $state): string {
                        if ($state instanceof SnippetLanguage) {
                            return $state->label();
                        }

                        return SnippetLanguage::tryFrom((string) $state)?->label() ?? (string) $state;
                    })
                    ->badge()
                    ->sortable(),
                TextColumn::make('version.name')
                    ->label('Version')
                    ->placeholder('All versions')
                    ->sortable(),
                TextColumn::make('package_name')
                    ->toggleable()
                    ->fontFamily('mono'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('api_version_id')
                    ->label('Version')
                    ->relationship('version', 'name'),
                SelectFilter::make('language')
                    ->options(
                        collect(SnippetLanguage::cases())
                            ->mapWithKeys(fn (SnippetLanguage $language) => [$language->value => $language->label()])
                            ->all()
                    ),
                SelectFilter::make('status'),
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
