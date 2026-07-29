<?php

namespace App\Filament\Resources\ApiVersions\Schemas;

use App\Filament\Support\PublishStatusField;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ApiVersionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Version')
                    ->columns(2)
                    ->schema([
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
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        PublishStatusField::make(),
                        Toggle::make('is_default')
                            ->label('Default version')
                            ->helperText('Portal uses this version when none is selected.'),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        DateTimePicker::make('released_at'),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Select::make('environments')
                            ->label('Environments')
                            ->relationship('environments', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Environments available in the portal switcher for this version.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
