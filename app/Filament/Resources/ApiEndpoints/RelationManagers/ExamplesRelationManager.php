<?php

namespace App\Filament\Resources\ApiEndpoints\RelationManagers;

use App\Filament\Support\JsonFormField;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExamplesRelationManager extends RelationManager
{
    protected static string $relationship = 'examples';

    protected static ?string $title = 'Examples';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('api_environment_id')
                    ->label('Environment')
                    ->relationship('environment', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                TextInput::make('response_status')
                    ->numeric()
                    ->minValue(100)
                    ->maxValue(599)
                    ->placeholder('200'),
                Textarea::make('description')
                    ->rows(2)
                    ->columnSpanFull(),
                JsonFormField::make('request', 'Request (JSON)'),
                JsonFormField::make('response', 'Response (JSON)'),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('environment.name')
                    ->label('Environment')
                    ->badge()
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('response_status')
                    ->label('Status')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('description')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('api_environment_id')
                    ->label('Environment')
                    ->relationship('environment', 'name'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
