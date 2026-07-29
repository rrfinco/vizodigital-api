<?php

namespace App\Filament\Resources\ApiEndpoints\RelationManagers;

use App\Enums\SnippetLanguage;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CodeSamplesRelationManager extends RelationManager
{
    protected static string $relationship = 'codeSamples';

    protected static ?string $title = 'Code samples';

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
                Select::make('language')
                    ->options(
                        collect(SnippetLanguage::cases())
                            ->mapWithKeys(fn (SnippetLanguage $language) => [$language->value => $language->label()])
                            ->all()
                    )
                    ->required()
                    ->live(),
                Toggle::make('is_generated')
                    ->label('Auto-generated')
                    ->default(false),
                Toggle::make('is_override')
                    ->label('Manual override')
                    ->helperText('Keep this sample even if snippets are regenerated.')
                    ->default(true),
                CodeEditor::make('code')
                    ->required()
                    ->columnSpanFull()
                    ->language(function (callable $get): Language {
                        return match ($get('language')) {
                            SnippetLanguage::Php->value, SnippetLanguage::Laravel->value => Language::Php,
                            SnippetLanguage::Python->value => Language::Python,
                            SnippetLanguage::Java->value => Language::Java,
                            SnippetLanguage::Go->value => Language::Go,
                            SnippetLanguage::Javascript->value, SnippetLanguage::Nodejs->value => Language::JavaScript,
                            default => Language::Html,
                        };
                    }),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('language')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('environment.name')
                    ->label('Environment')
                    ->badge()
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
                IconColumn::make('is_generated')
                    ->label('Generated')
                    ->boolean(),
                IconColumn::make('is_override')
                    ->label('Override')
                    ->boolean(),
                TextColumn::make('code')
                    ->limit(40)
                    ->fontFamily('mono')
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
                SelectFilter::make('language')
                    ->options(
                        collect(SnippetLanguage::cases())
                            ->mapWithKeys(fn (SnippetLanguage $language) => [$language->value => $language->label()])
                            ->all()
                    ),
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
