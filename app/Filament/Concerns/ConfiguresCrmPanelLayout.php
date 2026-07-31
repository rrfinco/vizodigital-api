<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Panel;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsIconAlias;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;

trait ConfiguresCrmPanelLayout
{
    protected function panelLogoutPath(): string
    {
        $logoutUrl = filament()->getLogoutUrl();
        $path = parse_url($logoutUrl, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : $logoutUrl;
    }

    protected function configureCrmLayout(Panel $panel, string $panelLabel): Panel
    {
        return $panel
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('17rem')
            ->collapsedSidebarWidth('4.5rem')
            ->topbar()
            ->userMenu()
            ->icons([
                PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON => Heroicon::OutlinedBars3,
                PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON_RTL => Heroicon::OutlinedBars3,
                PanelsIconAlias::SIDEBAR_EXPAND_BUTTON => Heroicon::OutlinedBars3,
                PanelsIconAlias::SIDEBAR_EXPAND_BUTTON_RTL => Heroicon::OutlinedBars3,
            ])
            ->userMenuItems([
                // Use Filament's default logout action, but force a host-agnostic path
                // so logout keeps working when APP_URL doesn't match the public host.
                'logout' => fn (Action $action): Action => $action
                    ->label('Log out')
                    ->url(fn (): string => $this->panelLogoutPath())
                    ->postToUrl(),
            ])
            ->maxContentWidth(Width::Full)
            ->font('Inter')
            ->viteTheme('resources/css/filament/theme.css')
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): View => view('filament.hooks.card-shadows'),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn (): View => view('filament.hooks.topbar-context', [
                    'label' => $panelLabel,
                ]),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_LOGO_AFTER,
                fn (): View => view('filament.hooks.sidebar-toggle'),
            )
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn (): View => view('filament.hooks.footer', [
                    'label' => $panelLabel,
                ]),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn (): View => view('filament.hooks.sidebar-footer'),
            );
    }
}
