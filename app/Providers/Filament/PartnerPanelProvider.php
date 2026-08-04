<?php

namespace App\Providers\Filament;

use App\Filament\Concerns\ConfiguresCrmPanelLayout;
use App\Filament\Partner\Pages\Dashboard as PartnerDashboard;
use App\Http\Middleware\ClearIncompatibleFilamentSession;
use App\Http\Middleware\EnsurePartnerPanelHost;
use App\Http\Middleware\FilamentAuthenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PartnerPanelProvider extends PanelProvider
{
    use ConfiguresCrmPanelLayout;

    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->id('partner')
            ->path('partner')
            ->login()
            ->brandName(fn (): string => app(\App\Services\Portal\PortalSettings::class)->whitelabelBrandName()
                ?: auth()->user()?->whitelabel?->brand_name
                ?: auth()->user()?->whitelabel?->name
                ?: 'PARTNER portal')
            ->brandLogo(fn (): HtmlString => new HtmlString(
                view('filament.hooks.brand-mark', [
                    'subtitle' => app(\App\Services\Portal\PortalSettings::class)->whitelabelBrandName()
                        ?: auth()->user()?->whitelabel?->brand_name
                        ?: auth()->user()?->whitelabel?->name
                        ?: 'PARTNER portal',
                    'logoHeight' => '1.75rem',
                ])->render()
            ))
            ->brandLogoHeight('auto')
            ->favicon(fn (): string => asset('images/brand/vizo-icon.jpg'))
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->navigationGroups([
                NavigationGroup::make('Workspace')->collapsible(false),
                NavigationGroup::make('Commissions')->collapsible(false),
            ])
            ->discoverResources(in: app_path('Filament/Partner/Resources'), for: 'App\\Filament\\Partner\\Resources')
            ->discoverPages(in: app_path('Filament/Partner/Pages'), for: 'App\\Filament\\Partner\\Pages')
            ->pages([
                PartnerDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Partner/Widgets'), for: 'App\\Filament\\Partner\\Widgets')
            ->middleware([
                EnsurePartnerPanelHost::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ...(app()->environment('local') ? [] : [AuthenticateSession::class]),
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                ClearIncompatibleFilamentSession::class,
            ])
            ->authMiddleware([
                FilamentAuthenticate::class,
            ]);

        return $this->configureCrmLayout($panel, 'PARTNER portal');
    }
}
