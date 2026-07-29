@props(['label', 'anchor'])

<section id="{{ $anchor }}" {{ $attributes->merge(['class' => 'mt-10']) }}>
    <h2 class="text-lg font-semibold text-portal-dark dark:text-white">{{ $label }}</h2>
    <div class="mt-3">
        {{ $slot }}
    </div>
</section>
