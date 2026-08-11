<?php

namespace App\Filament\Resources\Whitelabels\Schemas;

use App\Enums\RechargeProvider;
use App\Enums\WhitelabelStatus;
use App\Models\User;
use App\Models\Whitelabel;
use Filament\Forms\Components\FileUpload;
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
                        Select::make('recharge_provider')
                            ->label('Recharge provider')
                            ->options(RechargeProvider::class)
                            ->required()
                            ->default(RechargeProvider::Roundpay->value)
                            ->helperText('All developers under this white-label use this recharge API.'),
                        TextInput::make('wallet_balance')
                            ->label('Wallet balance')
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
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('whitelabel-logos')
                            ->visibility('public')
                            ->imagePreviewHeight('120')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
                            ->helperText('PNG, JPG, WebP or SVG. Max 2 MB. Shown on the partner portal and docs.')
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
                Section::make('Owner login')
                    ->columns(2)
                    ->description('Partner signs in at /partner with this email and password. Set a new password when you need to share credentials.')
                    ->visibleOn('edit')
                    ->schema([
                        TextInput::make('owner_name')
                            ->label('Owner name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('owner_email')
                            ->label('Owner email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                table: 'users',
                                column: 'email',
                                ignorable: fn (?Whitelabel $record): ?User => $record?->owner,
                            ),
                        TextInput::make('owner_password')
                            ->label('New password')
                            ->password()
                            ->revealable()
                            ->rule(Password::defaults())
                            ->helperText('Leave blank to keep the current password. Fill in to set a new one you can share with the partner.')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
