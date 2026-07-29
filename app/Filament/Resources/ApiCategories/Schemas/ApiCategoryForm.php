<?php

namespace App\Filament\Resources\ApiCategories\Schemas;

use App\Filament\Support\PublishStatusField;
use App\Models\ApiVersion;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ApiCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category')
                    ->columns(2)
                    ->schema([
                        Select::make('api_version_id')
                            ->label('API version')
                            ->relationship('version', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => ApiVersion::query()->where('is_default', true)->value('id')),
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
                        TextInput::make('icon')
                            ->helperText('Optional icon key for the portal sidebar.'),
                        PublishStatusField::make(),
                        Toggle::make('show_in_sidebar')
                            ->default(true),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
