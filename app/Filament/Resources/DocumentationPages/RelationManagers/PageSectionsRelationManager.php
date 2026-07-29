<?php

namespace App\Filament\Resources\DocumentationPages\RelationManagers;

use App\Filament\Support\JsonFormField;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PageSectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    protected static ?string $title = 'Page sections';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('section_key')
                    ->label('Section key')
                    ->required()
                    ->maxLength(255),
                TextInput::make('title')
                    ->maxLength(255),
                Toggle::make('enabled')
                    ->default(true),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
                MarkdownEditor::make('body_md')
                    ->label('Body')
                    ->columnSpanFull(),
                JsonFormField::make('config', 'Config JSON'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('section_key')
                    ->label('Key')
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable(),
                IconColumn::make('enabled')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
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
