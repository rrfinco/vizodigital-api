<?php

namespace App\Filament\Resources\Whitelabels\Schemas;

use App\Enums\WhitelabelStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class WhitelabelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Partner')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, callable $set): void {
                                if ($operation === 'create' && filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->alphaDash(),
                        Select::make('status')
                            ->options(WhitelabelStatus::class)
                            ->required()
                            ->default(WhitelabelStatus::Active->value),
                        TextInput::make('wallet_balance')
                            ->label('Float balance')
                            ->disabled()
                            ->dehydrated(false)
                            ->prefix('₹')
                            ->visibleOn('edit'),
                    ]),
                Section::make('Branding')
                    ->columns(2)
                    ->schema([
                        TextInput::make('brand_name')
                            ->maxLength(255),
                        TextInput::make('primary_color')
                            ->label('Primary color')
                            ->placeholder('#0F766E')
                            ->maxLength(32),
                        TextInput::make('logo_path')
                            ->label('Logo path')
                            ->helperText('Public disk path or URL (upload UI later).')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
                Section::make('Owner account')
                    ->columns(2)
                    ->description('Partner login for /partner panel. Created only when adding a white-label.')
                    ->visibleOn('create')
                    ->schema([
                        TextInput::make('owner_name')
                            ->label('Owner name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('owner_email')
                            ->label('Owner email')
                            ->email()
                            ->required()
                            ->unique(table: 'users', column: 'email')
                            ->maxLength(255),
                        TextInput::make('owner_password')
                            ->label('Owner password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->rule(Password::defaults())
                            ->columnSpanFull(),
                    ]),
                Section::make('Owner')
                    ->columns(2)
                    ->visibleOn('edit')
                    ->schema([
                        TextInput::make('owner.name')
                            ->label('Owner name')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('owner.email')
                            ->label('Owner email')
                            ->disabled()
                            ->dehydrated(false),
                    ]),
            ]);
    }
}
