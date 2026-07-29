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
}
