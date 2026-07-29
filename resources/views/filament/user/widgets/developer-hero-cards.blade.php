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

        <article class="dev-hero-card dev-hero-card--access">
            <div class="dev-hero-card__row">
                <div>
                    <p class="dev-hero-card__label">Environment</p>
                    <p class="dev-hero-card__metric">UAT</p>
                </div>
                <div class="dev-hero-card__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 1-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.611L5 14.5" />
                    </svg>
                </div>
            </div>
            <p class="dev-hero-card__sub">Prod unlocks after admin approval</p>
            <a href="{{ $docsUrl }}" target="_blank" rel="noopener noreferrer" class="dev-hero-card__cta">
                Open API Docs
            </a>
        </article>

        <article class="dev-hero-card dev-hero-card--docs">
            <p class="dev-hero-card__brand">vizodigital</p>
            <span class="dev-hero-card__pill">Keys · admin managed</span>
            <div class="dev-hero-card__body">
                <p class="dev-hero-card__title dev-hero-card__title--dark">Public docs · private credentials</p>
                <a href="{{ $portalUrl }}" class="dev-hero-card__link">Portal home →</a>
            </div>
        </article>
    </div>
</x-filament-widgets::widget>
