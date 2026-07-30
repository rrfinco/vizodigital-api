@extends('layouts.docs')

@section('title', $category->name . ' — ' . config('portal.name'))

@section('content')
    @php
        $endpoints = $category->groups->flatMap->endpoints->sortBy('sort_order')->values();
    @endphp
    <div class="mx-auto max-w-4xl">
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Category</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-[#0b1f3a] dark:text-white">{{ $category->name }}</h1>
        @if ($category->description)
            <p class="mt-3 text-base text-slate-600 dark:text-slate-300">{{ $category->description }}</p>
        @endif

        <ul class="mt-8 space-y-2">
            @forelse ($endpoints as $endpoint)
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
            @empty
                <li>
                    <x-docs.empty-state
                        title="No published APIs"
                        description="Publish endpoints under this category to list them here."
                    />
                </li>
            @endforelse
        </ul>
    </div>
@endsection
