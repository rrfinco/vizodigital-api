@props([
    'title' => 'Nothing here yet',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'portal-card border-dashed p-8 text-center dark:border-slate-700']) }}>
    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 dark:bg-primary-950/50 dark:text-primary-300">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
        </svg>
    </div>
    <h2 class="text-lg font-semibold text-portal-dark dark:text-white">{{ $title }}</h2>
    @if ($description)
        <p class="mx-auto mt-2 max-w-md text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
    @endif
    @if (isset($slot) && trim($slot) !== '')
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
