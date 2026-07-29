<?php

namespace App\Filament\Resources\NavigationItems\Schemas;

use App\Enums\NavigationTargetType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class NavigationItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Navigation item')
                    ->columns(2)
                    ->schema([
                        Select::make('api_version_id')
                            ->label('API version')
                            ->relationship('version', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Leave empty for a global item shown on all versions.'),
                        Select::make('parent_id')
                            ->label('Parent')
                            ->relationship('parent', 'label')
                            ->searchable()
                            ->preload(),
                        TextInput::make('label')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('icon')
                            ->maxLength(255),
                        Select::make('target_type')
                            ->label('Target type')
                            ->options(NavigationTargetType::class)
                            ->required()
                            ->live(),
                        TextInput::make('target_id')
                            ->label('Target ID')
                            ->numeric()
                            ->visible(fn (Get $get): bool => in_array($get('target_type'), [
                                NavigationTargetType::Page->value,
                                NavigationTargetType::Category->value,
                                NavigationTargetType::Group->value,
                                NavigationTargetType::Endpoint->value,
                            ], true)),
                        TextInput::make('url')
                            ->label('External URL')
                            ->url()
                            ->maxLength(255)
                            ->visible(fn (Get $get): bool => $get('target_type') === NavigationTargetType::Url->value),
                        TextInput::make('route_name')
                            ->label('Route name')
                            ->helperText('Optional named Laravel route, e.g. docs.overview')
                            ->maxLength(255),
                        Toggle::make('is_visible')
                            ->label('Visible')
                            ->default(true),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ]),
            ]);
    }
}
