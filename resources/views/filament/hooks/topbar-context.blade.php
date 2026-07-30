@php
    $user = auth()->user();
    $panelId = filament()->getCurrentPanel()?->getId();
    $isUserPanel = $panelId === 'user';
@endphp

<div class="crm-topbar-nav">
    @if ($isUserPanel && $user)
        <nav class="crm-topbar-links" aria-label="Quick navigation">
            <a
                href="{{ \App\Filament\User\Pages\Dashboard::getUrl(panel: 'user') }}"
                @class([
                    'crm-topbar-link',
                    'crm-topbar-link--active' => request()->routeIs('filament.user.pages.dashboard'),
                ])
            >
                Dashboard
            </a>
            <a
                href="{{ route('docs.overview') }}"
                target="_blank"
                rel="noopener noreferrer"
                class="crm-topbar-link"
            >
                API Docs
            </a>
        </nav>
    @endif

    <div class="crm-topbar-chip" title="{{ $label }}">
        <span class="inline-block size-1.5 rounded-full bg-emerald-500"></span>
        <span>{{ $label }}</span>
        @if ($user)
            <span class="opacity-40">·</span>
            <span class="font-medium opacity-80">{{ $user->name }}</span>
        @endif
    </div>
</div>
