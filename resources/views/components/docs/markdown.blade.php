@props(['html'])

@if (filled($html))
    <div {{ $attributes->merge(['class' => 'portal-prose text-sm leading-relaxed text-slate-700 dark:text-slate-300']) }}>
        {!! $html !!}
    </div>
@endif
