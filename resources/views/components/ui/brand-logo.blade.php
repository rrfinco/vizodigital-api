@props([
    'height' => null,
    'showText' => false,
    'href' => null,
])

@php
    $settings = app(\App\Services\Portal\PortalSettings::class);
    $logoUrl = $settings->logoUrl();
    $logoText = $settings->logoText();
    $logoHeight = $height ?? $settings->logoHeight();
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class(['inline-flex min-w-0 items-center gap-2.5']) }}>
@else
    <span {{ $attributes->class(['inline-flex min-w-0 items-center gap-2.5']) }}>
@endif
        <img
            src="{{ $logoUrl }}"
            alt="{{ $logoText }}"
            class="w-auto object-contain"
            style="height: {{ $logoHeight }}"
        />
        @if ($showText)
            <span class="truncate text-base font-semibold tracking-tight text-portal-dark dark:text-white">
                {{ $logoText }}
            </span>
        @endif
@if ($href)
    </a>
@else
    </span>
@endif
