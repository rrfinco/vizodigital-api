<?php

namespace App\Filament\Pages;

use App\Services\Portal\PortalSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Documentation CMS';

    protected static ?int $navigationSort = 100;

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'Portal settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public function mount(PortalSettings $settings): void
    {
        $this->form->fill([
            'name' => $settings->name(),
            'tagline' => $settings->tagline(),
            'logo_text' => $settings->logoText(),
            'roundpay_api_url' => $settings->roundpayApiUrl(),
            'roundpay_user_id' => $settings->roundpayUserId(),
            'roundpay_token' => $settings->roundpayToken(),
            'roundpay_route_type' => $settings->roundpayRouteType(),
            'roundpay_is_real' => $settings->roundpayIsReal(),
            'roundpay_format' => $settings->roundpayFormat(),
            'roundpay_default_geocode' => $settings->roundpayDefaultGeocode(),
            'roundpay_default_customer' => $settings->roundpayDefaultCustomer(),
            'roundpay_default_pincode' => $settings->roundpayDefaultPincode(),
            'rrfinco_account' => $settings->rrfincoAccount(),
            'rrfinco_merchant_id' => $settings->rrfincoMerchantId(),
            'rrfinco_api_token' => $settings->rrfincoApiToken(),
            'rrfinco_salt_key' => $settings->rrfincoSaltKey(),
            'inspay_username' => $settings->inspayUsername(),
            'inspay_token' => $settings->inspayToken(),
            'wallet_online_enabled' => $settings->walletOnlineEnabled(),
            'wallet_bank_transfer_enabled' => $settings->walletBankTransferEnabled(),
            'bank_account_name' => $settings->bankAccountName(),
            'bank_account_number' => $settings->bankAccountNumber(),
            'bank_ifsc' => $settings->bankIfsc(),
            'bank_name' => $settings->bankName(),
            'bank_upi_id' => $settings->bankUpiId(),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Branding')
                    ->description('Overrides config/portal.php values for the public portal and admin brand.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('name')
                            ->label('Portal name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('tagline')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('logo_text')
                            ->label('Logo text')
                            ->required()
                            ->maxLength(64),
                    ]),

                Section::make('Roundpay API Configuration')
                    ->description('Set up credentials and parameters for the Roundpay Recharge API integration.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('roundpay_api_url')
                            ->label('Roundpay API URL')
                            ->required()
                            ->url()
                            ->columnSpan('full')
                            ->maxLength(255),
                        TextInput::make('roundpay_user_id')
                            ->label('Roundpay User ID')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('roundpay_token')
                            ->label('Roundpay API Token')
                            ->password()
                            ->revealable()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('roundpay_route_type')
                            ->label('Route Type')
                            ->required()
                            ->maxLength(10),
                        TextInput::make('roundpay_is_real')
                            ->label('Is Real Transaction (1 = Yes, 0 = Test)')
                            ->required()
                            ->maxLength(10),
                        TextInput::make('roundpay_format')
                            ->label('Response Format (1 = JSON)')
                            ->required()
                            ->maxLength(10),
                        TextInput::make('roundpay_default_geocode')
                            ->label('Default GEO Code')
                            ->maxLength(50),
                        TextInput::make('roundpay_default_customer')
                            ->label('Default Customer Number')
                            ->maxLength(50),
                        TextInput::make('roundpay_default_pincode')
                            ->label('Default Pincode')
                            ->maxLength(20),
                    ]),

                Section::make('RRFinco Payment Configuration')
                    ->description('Set up credentials for the RRFinco Payment Gateway (Add Funds).')
                    ->columns(2)
                    ->schema([
                        TextInput::make('rrfinco_account')
                            ->label('Account Code')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('rrfinco_merchant_id')
                            ->label('Merchant ID')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('rrfinco_api_token')
                            ->label('API Bearer Token')
                            ->password()
                            ->revealable()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('rrfinco_salt_key')
                            ->label('Salt Key')
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                    ]),

                Section::make('Inspay Configuration')
                    ->description('Credentials for the Inspay payment integration.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('inspay_username')
                            ->label('Username')
                            ->maxLength(255),
                        TextInput::make('inspay_token')
                            ->label('Token')
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                    ]),

                Section::make('Wallet funding methods')
                    ->description('Control which add-funds options developers see, and the bank account details for manual transfers.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('wallet_online_enabled')
                            ->label('Enable online payment')
                            ->helperText('RRFinco gateway checkout')
                            ->default(true),
                        Toggle::make('wallet_bank_transfer_enabled')
                            ->label('Enable bank transfer')
                            ->helperText('Users transfer to your account, then you approve')
                            ->live()
                            ->default(false),
                        TextInput::make('bank_account_name')
                            ->label('Account holder name')
                            ->maxLength(255)
                            ->visible(fn ($get): bool => (bool) $get('wallet_bank_transfer_enabled'))
                            ->required(fn ($get): bool => (bool) $get('wallet_bank_transfer_enabled')),
                        TextInput::make('bank_account_number')
                            ->label('Account number')
                            ->maxLength(64)
                            ->visible(fn ($get): bool => (bool) $get('wallet_bank_transfer_enabled'))
                            ->required(fn ($get): bool => (bool) $get('wallet_bank_transfer_enabled')),
                        TextInput::make('bank_ifsc')
                            ->label('IFSC code')
                            ->maxLength(32)
                            ->visible(fn ($get): bool => (bool) $get('wallet_bank_transfer_enabled'))
                            ->required(fn ($get): bool => (bool) $get('wallet_bank_transfer_enabled')),
                        TextInput::make('bank_name')
                            ->label('Bank name')
                            ->maxLength(255)
                            ->visible(fn ($get): bool => (bool) $get('wallet_bank_transfer_enabled')),
                        TextInput::make('bank_upi_id')
                            ->label('UPI ID (optional)')
                            ->maxLength(255)
                            ->visible(fn ($get): bool => (bool) $get('wallet_bank_transfer_enabled'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function save(PortalSettings $settings): void
    {
        $state = $this->form->getState();

        $settings->set('portal.name', $state['name'], 'branding');
        $settings->set('portal.tagline', $state['tagline'], 'branding');
        $settings->set('portal.brand.logo_text', $state['logo_text'], 'branding');

        $settings->set('roundpay_api_url', $state['roundpay_api_url'], 'recharge');
        $settings->set('roundpay_user_id', $state['roundpay_user_id'], 'recharge');
        $settings->set('roundpay_token', $state['roundpay_token'], 'recharge');
        $settings->set('roundpay_route_type', $state['roundpay_route_type'], 'recharge');
        $settings->set('roundpay_is_real', $state['roundpay_is_real'], 'recharge');
        $settings->set('roundpay_format', $state['roundpay_format'], 'recharge');
        $settings->set('roundpay_default_geocode', $state['roundpay_default_geocode'], 'recharge');
        $settings->set('roundpay_default_customer', $state['roundpay_default_customer'], 'recharge');
        $settings->set('roundpay_default_pincode', $state['roundpay_default_pincode'], 'recharge');
        $settings->set('rrfinco_account', $state['rrfinco_account'], 'payment');
        $settings->set('rrfinco_merchant_id', $state['rrfinco_merchant_id'], 'payment');
        $settings->set('rrfinco_api_token', $state['rrfinco_api_token'], 'payment');
        $settings->set('rrfinco_salt_key', $state['rrfinco_salt_key'], 'payment');

        $settings->set('inspay_username', $state['inspay_username'] ?? '', 'payment');
        $settings->set('inspay_token', $state['inspay_token'] ?? '', 'payment');

        $settings->set('wallet_online_enabled', (bool) ($state['wallet_online_enabled'] ?? false), 'payment');
        $settings->set('wallet_bank_transfer_enabled', (bool) ($state['wallet_bank_transfer_enabled'] ?? false), 'payment');
        $settings->set('bank_account_name', $state['bank_account_name'] ?? '', 'payment');
        $settings->set('bank_account_number', $state['bank_account_number'] ?? '', 'payment');
        $settings->set('bank_ifsc', $state['bank_ifsc'] ?? '', 'payment');
        $settings->set('bank_name', $state['bank_name'] ?? '', 'payment');
        $settings->set('bank_upi_id', $state['bank_upi_id'] ?? '', 'payment');

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make([
                    Action::make('save')
                        ->label('Save settings')
                        ->submit('save')
                        ->keyBindings(['mod+s']),
                ]),
            ]);
    }
}
