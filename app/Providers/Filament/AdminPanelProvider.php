<?php

namespace App\Providers\Filament;

use App\Filament\Concerns\ConfiguresCrmPanelLayout;
use App\Http\Middleware\ClearIncompatibleFilamentSession;
use App\Http\Middleware\EnsurePlatformAdminHost;
use App\Http\Middleware\FilamentAuthenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    use ConfiguresCrmPanelLayout;

    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('ADMIN portal')
            ->brandLogo(fn (): HtmlString => new HtmlString(
                view('filament.hooks.brand-mark', [
                    'subtitle' => 'ADMIN portal',
                    'logoHeight' => '1.75rem',
                ])->render()
            ))
            ->brandLogoHeight('auto')
            ->favicon(fn (): string => asset('images/brand/vizo-icon.jpg'))
            ->colors([
                'primary' => Color::Blue,
            ])
            ->navigationGroups([
                NavigationGroup::make('Documentation CMS')->collapsible(),
                NavigationGroup::make('System')->collapsible(),
                NavigationGroup::make('Taxation')->collapsible(),
                NavigationGroup::make('White-label')->collapsible(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EnsurePlatformAdminHost::class,
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

        return $this->configureCrmLayout($panel, 'ADMIN portal');
    }
}
