<x-filament-widgets::widget>
    <div class="dev-hero-grid">
        <article class="dev-hero-card dev-hero-card--greet">
            <div class="dev-hero-card__badge">
                <time datetime="{{ now()->toDateString() }}">{{ $dateLabel }}</time>
            </div>
            <div class="dev-hero-card__body">
                <p class="dev-hero-card__eyebrow">{{ $greeting }}</p>
                <h2 class="dev-hero-card__title">{{ $userName }}</h2>
                <p class="dev-hero-card__hint">{{ $brandName }} · {{ $status }}</p>
            </div>
        </article>

        <article class="dev-hero-card dev-hero-card--wallet">
            <div class="dev-hero-card__row">
                <div>
                    <p class="dev-hero-card__label">Wallet balance</p>
                    <p class="dev-hero-card__metric">₹{{ number_format($floatBalance, 2) }}</p>
                </div>
            </div>
            <p class="dev-hero-card__sub">Wholesale cover for your developers’ API traffic</p>
            <a href="{{ $floatUrl }}" class="dev-hero-card__cta">+ Request top-up</a>
        </article>

        <article class="dev-hero-card dev-hero-card--earn">
            <div class="dev-hero-card__row">
                <div>
                    <p class="dev-hero-card__label">Developers</p>
                    <p class="dev-hero-card__metric">{{ $developerCount }}</p>
                </div>
            </div>
            <p class="dev-hero-card__sub">
                Pending KYC: {{ $pendingKyc }} · Pending top-ups: {{ $pendingFloat }}
            </p>
        </article>
    </div>
</x-filament-widgets::widget>
