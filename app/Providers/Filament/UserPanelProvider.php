<?php

namespace App\Providers\Filament;

use App\Filament\Concerns\ConfiguresCrmPanelLayout;
use App\Filament\User\Pages\Dashboard as UserDashboard;
use App\Filament\User\Pages\Profile as UserProfile;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class UserPanelProvider extends PanelProvider
{
    use ConfiguresCrmPanelLayout;

    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->id('user')
            ->path('user')
            ->login()
            ->brandName('USER portal')
            ->brandLogo(fn (): HtmlString => new HtmlString(
                view('filament.hooks.brand-mark', [
                    'subtitle' => 'USER portal',
                    'logoHeight' => '1.75rem',
                ])->render()
            ))
            ->brandLogoHeight('auto')
            ->favicon(fn (): string => asset('images/brand/vizo-icon.jpg'))
            ->colors([
                'primary' => Color::Teal,
            ])
            ->navigationGroups([
                NavigationGroup::make('Workspace')->collapsible(false),
            ])
            ->discoverPages(in: app_path('Filament/User/Pages'), for: 'App\\Filament\\User\\Pages')
            ->pages([
                UserDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/User/Widgets'), for: 'App\\Filament\\User\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ...(app()->environment('local') ? [] : [AuthenticateSession::class]),
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        return $this->configureCrmLayout($panel, 'USER portal')
            ->userMenuItems([
                'profile' => Action::make('profile')
                    ->label('Profile')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->url(fn (): string => UserProfile::getUrl())
                    ->sort(10),
                'logout' => fn (Action $action): Action => $action
                    ->label('Log out')
                    ->icon(Heroicon::ArrowLeftEndOnRectangle)
                    ->url(fn (): string => $this->panelLogoutPath())
                    ->postToUrl(),
            ]);
    }
}
