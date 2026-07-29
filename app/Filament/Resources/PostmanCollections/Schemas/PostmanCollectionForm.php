<?php

namespace App\Filament\Resources\PostmanCollections\Schemas;

use App\Filament\Support\PublishStatusField;
use App\Filament\Support\JsonFormField;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostmanCollectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Postman collection')
                    ->columns(2)
                    ->schema([
                        Select::make('api_version_id')
                            ->label('API version')
                            ->relationship('version', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('api_environment_id')
                            ->label('Environment')
                            ->relationship('environment', 'name')
                            ->searchable()
                            ->preload()
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
                        PublishStatusField::make(),
                        TextInput::make('file_path')
                            ->label('File path')
                            ->helperText('Optional stored collection path')
                            ->maxLength(255),
                        JsonFormField::make('payload', 'Collection JSON'),
                    ]),
            ]);
    }
}
