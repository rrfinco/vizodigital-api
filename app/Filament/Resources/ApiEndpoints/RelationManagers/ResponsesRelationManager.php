<?php

namespace App\Filament\Resources\ApiEndpoints\RelationManagers;

use App\Filament\Support\JsonFormField;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ResponsesRelationManager extends RelationManager
{
    protected static string $relationship = 'responses';

    protected static ?string $title = 'Responses';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('status_code')
                    ->numeric()
                    ->required()
                    ->minValue(100)
                    ->maxValue(599)
                    ->default(200),
                TextInput::make('content_type')
                    ->required()
                    ->default('application/json')
                    ->maxLength(255),
                TextInput::make('description')
                    ->maxLength(255)
                    ->columnSpanFull(),
                Toggle::make('is_default')
                    ->label('Default response')
                    ->default(false),
                JsonFormField::make('schema', 'Schema (JSON)'),
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
            ->recordTitleAttribute('status_code')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('status_code')
                    ->badge()
                    ->sortable()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 200 && $state < 300 => 'success',
                        $state >= 400 && $state < 500 => 'warning',
                        $state >= 500 => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('description')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('content_type')
                    ->fontFamily('mono')
                    ->toggleable(),
                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
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
