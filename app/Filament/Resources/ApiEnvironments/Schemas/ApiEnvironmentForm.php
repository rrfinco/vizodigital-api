<?php

namespace App\Filament\Resources\ApiEnvironments\Schemas;

use App\Enums\EnvironmentSlug;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ApiEnvironmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Environment')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('slug')
                            ->options(EnvironmentSlug::class)
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('label')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('base_url')
                            ->url()
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('badge')
                            ->maxLength(50),
                        TextInput::make('color')
                            ->placeholder('#2563EB')
                            ->maxLength(20),
                        Toggle::make('is_default')
                            ->label('Default environment'),
                        Toggle::make('is_enabled')
                            ->default(true),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ]),
            ]);
    }
}
