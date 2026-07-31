@php
    $settings = app(\App\Services\Portal\PortalSettings::class);
    $subtitle = $subtitle ?? null;
@endphp

<span class="flex flex-col items-start justify-center gap-0.5 leading-none">
    <img
        src="{{ $settings->logoUrl() }}"
        alt="{{ $settings->logoText() }}"
        class="w-auto object-contain"
        style="height: {{ $logoHeight ?? '1.75rem' }}"
    />
    @if ($subtitle)
        <span class="text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-400">
            {{ $subtitle }}
        </span>
    @endif
</span>
