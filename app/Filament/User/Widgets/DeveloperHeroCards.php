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
            'docsUrl' => route('docs.overview'),
            'portalUrl' => url('/'),
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
