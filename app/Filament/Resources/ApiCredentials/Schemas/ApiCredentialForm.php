<?php

namespace App\Filament\Resources\ApiCredentials\Schemas;

use App\Enums\CredentialStatus;
use App\Enums\Role as RoleEnum;
use App\Models\ApiEnvironment;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ApiCredentialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Assignment')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Developer')
                            ->options(
                                fn (): array => User::query()
                                    ->role(RoleEnum::Developer->value)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all()
                            )
                            ->searchable()
                            ->required(),
                        Select::make('api_environment_id')
                            ->label('Environment')
                            ->options(
                                fn (): array => ApiEnvironment::query()
                                    ->where('is_enabled', true)
                                    ->orderBy('sort_order')
                                    ->pluck('label', 'id')
                                    ->all()
                            )
                            ->required(),
                        Select::make('status')
                            ->options(CredentialStatus::class)
                            ->required()
                            ->default(CredentialStatus::Active->value),
                    ]),
                Section::make('Keys')
                    ->columns(2)
                    ->schema([
                        TextInput::make('client_id')
                            ->label('Client ID')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('api_secret')
                            ->label('API Secret')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(255),
                        TextInput::make('merchant_id')
                            ->label('Merchant ID')
                            ->maxLength(255),
                        TextInput::make('webhook_secret')
                            ->label('Webhook Secret')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(255),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
