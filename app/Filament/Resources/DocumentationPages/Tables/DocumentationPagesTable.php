<?php

namespace App\Filament\Resources\DocumentationPages\Tables;

use App\Enums\DocPageType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DocumentationPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->formatStateUsing(function (mixed $state): string {
                        if ($state instanceof DocPageType) {
                            return $state->label();
                        }

                        return DocPageType::tryFrom((string) $state)?->label() ?? (string) $state;
                    })
                    ->badge()
                    ->sortable(),
                TextColumn::make('version.name')
                    ->label('Version')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                IconColumn::make('show_in_sidebar')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('api_version_id')
                    ->label('Version')
                    ->relationship('version', 'name'),
                SelectFilter::make('type')
                    ->options(
                        collect(DocPageType::cases())
                            ->mapWithKeys(fn (DocPageType $type) => [$type->value => $type->label()])
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
