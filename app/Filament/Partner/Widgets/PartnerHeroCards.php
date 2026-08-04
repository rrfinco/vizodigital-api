<?php

namespace App\Filament\Partner\Widgets;

use App\Enums\OnboardingStatus;
use App\Enums\Role;
use App\Filament\Partner\Pages\FloatWallet;
use App\Models\User;
use App\Models\WhitelabelFloatRequest;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class PartnerHeroCards extends Widget
{
    protected string $view = 'filament.partner.widgets.partner-hero-cards';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -1;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = auth()->user();
        $whitelabel = $user?->whitelabel;
        $wlId = $user?->whitelabel_id;
        $now = now();

        $developerCount = $wlId
            ? User::query()->role(Role::Developer->value)->where('whitelabel_id', $wlId)->count()
            : 0;

        $pendingKyc = $wlId
            ? User::query()
                ->role(Role::Developer->value)
                ->where('whitelabel_id', $wlId)
                ->where('onboarding_status', OnboardingStatus::KycSubmitted->value)
                ->count()
            : 0;

        $pendingFloat = $wlId
            ? WhitelabelFloatRequest::query()
                ->where('whitelabel_id', $wlId)
                ->where('status', WhitelabelFloatRequest::STATUS_PENDING)
                ->count()
            : 0;

        return [
            'userName' => $user?->name ?? 'Partner',
            'brandName' => $whitelabel?->brand_name ?: $whitelabel?->name ?: 'Partner',
            'dateLabel' => $now->format('d M Y'),
            'greeting' => $this->greetingFor($now),
            'floatBalance' => (float) ($whitelabel?->wallet_balance ?? 0),
            'developerCount' => $developerCount,
            'pendingKyc' => $pendingKyc,
            'pendingFloat' => $pendingFloat,
            'floatUrl' => FloatWallet::getUrl(),
            'status' => $whitelabel?->status?->label() ?? '—',
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
