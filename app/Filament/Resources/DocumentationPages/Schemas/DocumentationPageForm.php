<?php

namespace App\Filament\Resources\DocumentationPages\Schemas;

use App\Enums\DocPageType;
use App\Filament\Support\PublishStatusField;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class DocumentationPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Documentation page')
                    ->columns(2)
                    ->schema([
                        Select::make('api_version_id')
                            ->label('API version')
                            ->relationship('version', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('parent_id')
                            ->label('Parent page')
                            ->relationship('parent', 'title')
                            ->searchable()
                            ->preload(),
                        Select::make('type')
                            ->options(
                                collect(DocPageType::cases())
                                    ->mapWithKeys(fn (DocPageType $type) => [$type->value => $type->label()])
                                    ->all()
                            )
                            ->required()
                            ->default(DocPageType::Guide->value),
                        PublishStatusField::make(),
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, callable $set, ?string $operation): void {
                                if ($operation === 'create' && filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('sidebar_key')
                            ->maxLength(255),
                        Toggle::make('show_in_sidebar')
                            ->label('Show in sidebar')
                            ->default(true),
                        DateTimePicker::make('published_at'),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        MarkdownEditor::make('body_md')
                            ->label('Body')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
