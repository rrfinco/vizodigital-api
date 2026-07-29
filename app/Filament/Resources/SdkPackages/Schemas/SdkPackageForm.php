<?php

namespace App\Filament\Resources\SdkPackages\Schemas;

use App\Filament\Support\PublishStatusField;
use App\Enums\SnippetLanguage;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class SdkPackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('SDK package')
                    ->columns(2)
                    ->schema([
                        Select::make('api_version_id')
                            ->label('API version')
                            ->relationship('version', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('language')
                            ->options(
                                collect(SnippetLanguage::cases())
                                    ->mapWithKeys(fn (SnippetLanguage $language) => [$language->value => $language->label()])
                                    ->all()
                            )
                            ->required(),
                        TextInput::make('name')
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
                        TextInput::make('package_name')
                            ->placeholder('acme/api-sdk')
                            ->maxLength(255),
                        TextInput::make('repo_url')
                            ->url()
                            ->maxLength(255),
                        PublishStatusField::make(),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        MarkdownEditor::make('install_md')
                            ->label('Install instructions')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
