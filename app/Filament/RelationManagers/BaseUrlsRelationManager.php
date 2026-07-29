<?php

namespace App\Filament\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BaseUrlsRelationManager extends RelationManager
{
    protected static string $relationship = 'baseUrls';

    protected static ?string $title = 'Base URL overrides';

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
                TextInput::make('base_url')
                    ->label('Base URL')
                    ->url()
                    ->required()
                    ->maxLength(255)
                    ->placeholder('https://uat-api.example.com')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('base_url')
            ->columns([
                TextColumn::make('environment.name')
                    ->label('Environment')
                    ->badge()
                    ->sortable(),
                TextColumn::make('base_url')
                    ->label('Base URL')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),
                TextColumn::make('updated_at')
                    ->dateTime()
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
