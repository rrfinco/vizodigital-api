<?php

namespace App\Filament\Resources\ApiEndpoints\RelationManagers;

use App\Enums\ParameterLocation;
use App\Filament\Support\JsonFormField;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ParametersRelationManager extends RelationManager
{
    protected static string $relationship = 'parameters';

    protected static ?string $title = 'Parameters';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('location')
                    ->options(ParameterLocation::class)
                    ->required()
                    ->default(ParameterLocation::Query->value),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('page'),
                TextInput::make('type')
                    ->required()
                    ->default('string')
                    ->maxLength(100),
                Toggle::make('required')
                    ->default(false),
                TextInput::make('example')
                    ->maxLength(255),
                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                JsonFormField::make('schema', 'Schema (JSON)'),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('location')
                    ->badge()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                TextColumn::make('type')
                    ->badge(),
                IconColumn::make('required')
                    ->boolean(),
                TextColumn::make('example')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('location')
                    ->options(ParameterLocation::class),
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
