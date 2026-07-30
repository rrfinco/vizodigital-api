<?php

namespace App\Filament\User\Pages;

use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Services\Subscription\SubscriptionPurchaseService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class Subscription extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 25;

    protected static ?string $navigationLabel = 'Subscription';

    protected static ?string $title = 'Subscription Plans';

    protected string $view = 'filament.user.pages.subscription';

    /**
     * @return Collection<int, SubscriptionPlan>
     */
    public function getPlans(): Collection
    {
        return SubscriptionPlan::query()
            ->where('is_active', true)
            ->with(['endpoints' => fn ($query) => $query->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();
    }

    public function getActiveSubscription(): ?UserSubscription
    {
        return auth()->user()?->activeSubscription()?->load('plan');
    }

    public function buyNow(int $planId, SubscriptionPurchaseService $purchaseService): void
    {
        $plan = SubscriptionPlan::query()->findOrFail($planId);

        try {
            $subscription = $purchaseService->purchase(auth()->user(), $plan);

            Notification::make()
                ->title('Plan activated')
                ->body(
                    "{$plan->name} is active until "
                    . $subscription->ends_at->format('d M Y')
                    . '. ₹'
                    . number_format((float) $subscription->amount_paid, 2)
                    . ' deducted from wallet.'
                )
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Log::warning('Subscription purchase failed', [
                'user_id' => auth()->id(),
                'plan_id' => $planId,
                'message' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Purchase failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
