@php
    $plans = $this->getPlans();
    $activeSubscription = $this->getActiveSubscription();
    $activePlanId = $activeSubscription?->subscription_plan_id;
    $walletBalance = (float) auth()->user()->wallet_balance;
@endphp

<x-filament-panels::page>
    <div class="subscription-page space-y-6">
        <div class="subscription-page__intro">
            <p class="text-sm text-gray-600 dark:text-gray-300 -mt-2">
                Choose a plan and buy with your wallet balance. Current wallet:
                <strong>₹{{ number_format($walletBalance, 2) }}</strong>
                ·
                <a href="{{ \App\Filament\User\Pages\Wallet::getUrl(panel: 'user') }}" class="text-teal-700 underline dark:text-teal-300">Add funds</a>
            </p>

            @if ($activeSubscription)
                <div class="subscription-active-banner">
                    <div>
                        <p class="subscription-active-banner__label">Active plan</p>
                        <p class="subscription-active-banner__value">
                            {{ $activeSubscription->plan?->name ?? 'Plan' }}
                            · valid till {{ $activeSubscription->ends_at->format('d M Y') }}
                        </p>
                    </div>
                </div>
            @endif
        </div>

        @if ($plans->isEmpty())
            <div class="subscription-empty">
                <div class="subscription-empty__icon" aria-hidden="true">
                    <x-filament::icon icon="heroicon-o-credit-card" class="h-7 w-7" />
                </div>
                <p class="subscription-empty__title">No plans available</p>
                <p class="subscription-empty__hint">
                    Subscription plans will appear here once an admin publishes them.
                </p>
            </div>
        @else
            <div class="subscription-grid">
                @foreach ($plans as $index => $plan)
                    @php
                        $featured = $plans->count() >= 3 && $index === 1;
                        $isCurrent = $activePlanId === $plan->id;
                        $canAfford = $walletBalance >= (float) $plan->price;
                    @endphp
                    <article @class([
                        'subscription-card',
                        'subscription-card--featured' => $featured,
                        'subscription-card--current' => $isCurrent,
                    ])>
                        @if ($isCurrent)
                            <span class="subscription-card__badge subscription-card__badge--current">Current</span>
                        @elseif ($featured)
                            <span class="subscription-card__badge">Popular</span>
                        @endif

                        <div class="subscription-card__header">
                            <h2 class="subscription-card__name">{{ $plan->name }}</h2>
                            @if ($plan->description)
                                <p class="subscription-card__desc">{{ $plan->description }}</p>
                            @endif
                        </div>

                        <div class="subscription-card__price-wrap">
                            <span class="subscription-card__currency">₹</span>
                            <span class="subscription-card__price">{{ number_format((float) $plan->price, 0) }}</span>
                            <span class="subscription-card__period">/ {{ $plan->duration_days }} days</span>
                        </div>

                        <ul class="subscription-card__services">
                            @forelse ($plan->endpoints as $endpoint)
                                <li>
                                    <x-filament::icon icon="heroicon-m-check" class="subscription-card__check" />
                                    <span>
                                        <span class="subscription-card__method">{{ $endpoint->method?->value ?? 'API' }}</span>
                                        {{ $endpoint->name }}
                                    </span>
                                </li>
                            @empty
                                <li class="subscription-card__muted">No services listed yet</li>
                            @endforelse
                        </ul>

                        <div class="subscription-card__footer">
                            <span class="subscription-card__meta">
                                {{ $plan->endpoints->count() }}
                                {{ \Illuminate\Support\Str::plural('service', $plan->endpoints->count()) }}
                            </span>

                            @if ($isCurrent)
                                <button type="button" class="subscription-card__btn subscription-card__btn--disabled" disabled>
                                    Active
                                </button>
                            @else
                                <button
                                    type="button"
                                    wire:click="buyNow({{ $plan->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="buyNow({{ $plan->id }})"
                                    @class([
                                        'subscription-card__btn',
                                        'subscription-card__btn--warn' => ! $canAfford && (float) $plan->price > 0,
                                    ])
                                >
                                    <span wire:loading.remove wire:target="buyNow({{ $plan->id }})">Buy Now</span>
                                    <span wire:loading wire:target="buyNow({{ $plan->id }})">Processing…</span>
                                </button>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
