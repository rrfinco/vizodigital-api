<?php

namespace App\Filament\User\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class DeveloperHeroCards extends Widget
{
    protected string $view = 'filament.user.widgets.developer-hero-cards';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -1;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = auth()->user();
        $now = now();

        return [
            'userName' => $user?->name ?? 'Developer',
            'dateLabel' => $now->format('d M Y'),
            'greeting' => $this->greetingFor($now),
            'walletBalance' => (float) ($user?->wallet_balance ?? 0),
            'earningBalance' => (float) ($user?->earning_balance ?? 0),
            'walletUrl' => \App\Filament\User\Pages\Wallet::getUrl(),
        ];
    }

    protected function greetingFor(Carbon $now): string
    {
        $hour = (int) $now->format('G');

        return match (true) {
            $hour < 12 => 'Good morning',
            $hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };
    }
}
