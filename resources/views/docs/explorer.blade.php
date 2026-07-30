@extends('layouts.docs')

@section('title', 'API Explorer — ' . $version->name . ' — ' . config('portal.name'))

@section('content')
    <div class="mx-auto max-w-4xl">
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Reference</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-[#0b1f3a] dark:text-white">API Explorer</h1>
        <p class="mt-3 text-base text-slate-600 dark:text-slate-300">
            Published endpoints for {{ $version->name }}
            @if ($environment)
                · {{ $environment->label ?: $environment->name }}
            @endif
        </p>

        @forelse ($categories as $category)
            @php
                $endpoints = $category->groups->flatMap->endpoints->sortBy('sort_order')->values();
            @endphp
            <section id="category-{{ $category->slug }}" class="mt-10">
                <h2 class="text-lg font-semibold text-[#0b1f3a] dark:text-white">
                    <a href="{{ route('docs.categories.show', ['version' => $version->slug, 'category' => $category->slug]) }}" class="hover:text-sky-700">
                        {{ $category->name }}
                    </a>
                </h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($endpoints as $endpoint)
                        <li>
                            <a
                                href="{{ route('docs.endpoints.show', ['version' => $version->slug, 'endpoint' => $endpoint->slug]) }}"
                                class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm transition hover:border-sky-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-950 dark:hover:border-sky-900"
                            >
                                <span @class([
                                    'rounded-md px-2 py-0.5 font-mono text-[10px] font-bold uppercase',
                                    'bg-emerald-50 text-emerald-700' => strtoupper((string) $endpoint->method?->value) === 'GET',
                                    'bg-violet-50 text-violet-700' => strtoupper((string) $endpoint->method?->value) === 'POST',
                                    'bg-slate-900 text-white' => ! in_array(strtoupper((string) $endpoint->method?->value), ['GET', 'POST'], true),
                                ])>
                                    {{ $endpoint->method?->value }}
                                </span>
                                <span class="font-medium text-[#0b1f3a] dark:text-white">{{ $endpoint->name }}</span>
                                <code class="ml-auto hidden font-mono text-xs text-slate-400 sm:inline">{{ $endpoint->path }}</code>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <div class="mt-10">
                <x-docs.empty-state
                    title="No published APIs yet"
                    description="Publish categories and endpoints from the admin CMS to populate this explorer."
                />
            </div>
        @endforelse
    </div>
@endsection
