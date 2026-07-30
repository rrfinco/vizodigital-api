<?php

namespace App\Services\Portal;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class PortalSettings
{
    public const CACHE_KEY = 'portal.settings.branding';

    public function get(string $key, mixed $default = null): mixed
    {
        return Setting::getValue($key, $default);
    }

    public function set(string $key, mixed $value, string $group = 'general'): Setting
    {
        $setting = Setting::setValue($key, $value, $group);
        $this->forgetCache();

        return $setting;
    }

    public function name(): string
    {
        return (string) ($this->branding()['name'] ?? config('portal.name'));
    }

    public function tagline(): string
    {
        return (string) ($this->branding()['tagline'] ?? config('portal.tagline'));
    }

    public function logoText(): string
    {
        return (string) ($this->branding()['logo_text'] ?? config('portal.brand.logo_text'));
    }

    /**
     * @return array{name: mixed, tagline: mixed, logo_text: mixed}
     */
    public function branding(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function (): array {
            return [
                'name' => Setting::getValue('portal.name', config('portal.name')),
                'tagline' => Setting::getValue('portal.tagline', config('portal.tagline')),
                'logo_text' => Setting::getValue('portal.brand.logo_text', config('portal.brand.logo_text')),
            ];
        });
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function roundpayApiUrl(): string
    {
        return (string) $this->get('roundpay_api_url', 'https://api.roundpay.net/API/TransactionAPI');
    }

    public function roundpayUserId(): string
    {
        return (string) $this->get('roundpay_user_id', '');
    }

    public function roundpayToken(): string
    {
        return (string) $this->get('roundpay_token', '');
    }

    public function roundpayRouteType(): string
    {
        return (string) $this->get('roundpay_route_type', '3');
    }

    public function roundpayIsReal(): string
    {
        return (string) $this->get('roundpay_is_real', '1');
    }

    public function roundpayFormat(): string
    {
        return (string) $this->get('roundpay_format', '1');
    }

    public function roundpayDefaultGeocode(): string
    {
        return (string) $this->get('roundpay_default_geocode', '80.94,26.85');
    }

    public function roundpayDefaultCustomer(): string
    {
        return (string) $this->get('roundpay_default_customer', '9999999999');
    }

    public function roundpayDefaultPincode(): string
    {
        return (string) $this->get('roundpay_default_pincode', '226001');
    }

    public function rrfincoAccount(): string
    {
        return (string) $this->get('rrfinco_account', '');
    }

    public function rrfincoMerchantId(): string
    {
        return (string) $this->get('rrfinco_merchant_id', '');
    }

    public function rrfincoApiToken(): string
    {
        return (string) $this->get('rrfinco_api_token', '');
    }

    public function rrfincoSaltKey(): string
    {
        return (string) $this->get('rrfinco_salt_key', '');
    }

    public function inspayUsername(): string
    {
        return (string) $this->get('inspay_username', '');
    }

    public function inspayToken(): string
    {
        return (string) $this->get('inspay_token', '');
    }

    public function walletOnlineEnabled(): bool
    {
        return (bool) $this->get('wallet_online_enabled', true);
    }

    public function walletBankTransferEnabled(): bool
    {
        return (bool) $this->get('wallet_bank_transfer_enabled', false);
    }

    public function bankAccountName(): string
    {
        return (string) $this->get('bank_account_name', '');
    }

    public function bankAccountNumber(): string
    {
        return (string) $this->get('bank_account_number', '');
    }

    public function bankIfsc(): string
    {
        return (string) $this->get('bank_ifsc', '');
    }

    public function bankName(): string
    {
        return (string) $this->get('bank_name', '');
    }

    public function bankUpiId(): string
    {
        return (string) $this->get('bank_upi_id', '');
    }

    /**
     * @return array{account_name: string, account_number: string, ifsc: string, bank_name: string, upi_id: string}
     */
    public function bankTransferDetails(): array
    {
        return [
            'account_name' => $this->bankAccountName(),
            'account_number' => $this->bankAccountNumber(),
            'ifsc' => $this->bankIfsc(),
            'bank_name' => $this->bankName(),
            'upi_id' => $this->bankUpiId(),
        ];
    }
}
