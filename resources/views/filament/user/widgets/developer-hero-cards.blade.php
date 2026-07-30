<x-filament-widgets::widget>
    <div class="dev-hero-grid">
        <article class="dev-hero-card dev-hero-card--greet">
            <div class="dev-hero-card__badge">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c0-.69.56-1.25 1.25-1.25h8.5c.69 0 1.25.56 1.25 1.25v7.5c0 .69-.56 1.25-1.25 1.25h-8.5c-.69 0-1.25-.56-1.25-1.25v-7.5Z" clip-rule="evenodd" />
                </svg>
                <time datetime="{{ now()->toDateString() }}">{{ $dateLabel }}</time>
            </div>
            <div class="dev-hero-card__body">
                <p class="dev-hero-card__eyebrow">{{ $greeting }}</p>
                <h2 class="dev-hero-card__title">{{ $userName }}</h2>
                <p class="dev-hero-card__hint">Developer workspace</p>
            </div>
        </article>

        <article class="dev-hero-card dev-hero-card--wallet">
            <div class="dev-hero-card__row">
                <div>
                    <p class="dev-hero-card__label">Main Wallet</p>
                    <p class="dev-hero-card__metric">₹{{ number_format($walletBalance, 2) }}</p>
                </div>
                <div class="dev-hero-card__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3" />
                    </svg>
                </div>
            </div>
            <p class="dev-hero-card__sub">Available for recharge APIs</p>
            <a href="{{ $walletUrl }}" class="dev-hero-card__cta">
                Add Funds
            </a>
        </article>

        <article class="dev-hero-card dev-hero-card--earn">
            <div class="dev-hero-card__row">
                <div>
                    <p class="dev-hero-card__label">My Earnings</p>
                    <p class="dev-hero-card__metric">₹{{ number_format($earningBalance, 2) }}</p>
                </div>
                <div class="dev-hero-card__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                    </svg>
                </div>
            </div>
            <p class="dev-hero-card__sub">Lifetime commission earnings</p>
            <a href="{{ $walletUrl }}" class="dev-hero-card__cta">
                View Wallet
            </a>
        </article>
    </div>
</x-filament-widgets::widget>
