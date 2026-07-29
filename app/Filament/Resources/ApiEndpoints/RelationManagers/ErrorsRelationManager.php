<?php

namespace App\Filament\Resources\ApiEndpoints\RelationManagers;

use App\Filament\Support\JsonFormField;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ErrorsRelationManager extends RelationManager
{
    protected static string $relationship = 'errors';

    protected static ?string $title = 'Errors';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('error_code')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('VALIDATION_ERROR'),
                TextInput::make('status_code')
                    ->numeric()
                    ->required()
                    ->minValue(100)
                    ->maxValue(599)
                    ->default(400),
                TextInput::make('message')
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                JsonFormField::make('example', 'Example (JSON)'),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('error_code')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('error_code')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                TextColumn::make('status_code')
                    ->badge()
                    ->sortable()
                    ->color('warning'),
                TextColumn::make('message')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
