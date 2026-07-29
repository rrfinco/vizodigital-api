<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Panel;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;

trait ConfiguresCrmPanelLayout
{
    protected function configureCrmLayout(Panel $panel, string $panelLabel): Panel
    {
        return $panel
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('17rem')
            ->collapsedSidebarWidth('4.5rem')
            ->topbar()
            ->userMenu()
            ->userMenuItems([
                'logout' => Action::make('logout')
                    ->label('Log out')
                    ->icon(Heroicon::ArrowLeftEndOnRectangle)
                    ->url(fn (): string => filament()->getLogoutUrl())
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
