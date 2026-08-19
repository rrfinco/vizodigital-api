<?php

namespace App\Filament\Resources\TaxationServices\Schemas;

use App\Models\TaxationCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TaxationServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Service')
                    ->columns(2)
                    ->schema([
                        TextInput::make('id')
                            ->label('Service ID')
                            ->numeric()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->disabledOn('edit')
                            ->helperText('Stable API key. Developers send this as service_id.'),
                        Select::make('taxation_category_id')
                            ->label('Category')
                            ->options(fn () => TaxationCategory::query()->orderBy('sort_order')->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('price')
                            ->numeric()
                            ->prefix('₹')
                            ->required()
                            ->minValue(0)
                            ->helperText('This amount is deducted from the developer wallet when they confirm an order.'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ]),
            ]);
    }
}
