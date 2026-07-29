@extends('layouts.docs')

@section('title', 'API Explorer — ' . $version->name . ' — ' . config('portal.name'))

@section('toc')
    <ul class="space-y-2 text-sm">
        @foreach ($categories as $category)
            <li>
                <a href="#category-{{ $category->slug }}" class="text-slate-600 hover:text-primary-600 dark:text-slate-300 dark:hover:text-primary-400">
                    {{ $category->name }}
                </a>
            </li>
        @endforeach
    </ul>
@endsection

@section('content')
    <div class="mx-auto max-w-3xl">
        <nav class="mb-6 text-sm text-slate-500">
            <a href="{{ route('docs.overview') }}" class="hover:text-primary-600">Docs</a>
            <span class="mx-2">/</span>
            <span class="text-slate-800 dark:text-slate-200">{{ $version->name }}</span>
        </nav>

        <h1 class="text-3xl font-semibold tracking-tight text-portal-dark dark:text-white">API Explorer</h1>
        <p class="mt-3 text-base text-slate-600 dark:text-slate-300">
            Published endpoints for {{ $version->name }}
            @if ($environment)
                · {{ $environment->label ?: $environment->name }}
            @endif
        </p>

        @forelse ($categories as $category)
            <section id="category-{{ $category->slug }}" class="mt-10">
                <h2 class="text-lg font-semibold text-portal-dark dark:text-white">{{ $category->name }}</h2>
                @foreach ($category->groups as $group)
                    <div class="mt-4">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-400">{{ $group->name }}</h3>
                        <ul class="mt-2 space-y-2">
                            @foreach ($group->endpoints as $endpoint)
                                <li>
                                    <a
                                        href="{{ route('docs.endpoints.show', ['version' => $version->slug, 'endpoint' => $endpoint->slug]) }}"
                                        class="flex items-center gap-3 rounded-2xl border border-portal-border px-4 py-3 text-sm transition hover:border-primary-300 hover:bg-primary-50/50 dark:border-slate-800 dark:hover:border-primary-800 dark:hover:bg-primary-950/20"
                                    >
                                        <span class="rounded-xl bg-slate-900 px-2 py-0.5 font-mono text-[10px] font-semibold uppercase text-white dark:bg-slate-100 dark:text-slate-900">
                                            {{ $endpoint->method?->value }}
                                        </span>
                                        <span class="font-medium text-portal-dark dark:text-white">{{ $endpoint->name }}</span>
                                        <code class="ml-auto hidden font-mono text-xs text-slate-400 sm:inline">{{ $endpoint->path }}</code>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </section>
        @empty
            <div class="mt-10">
                <x-docs.empty-state
                    title="No published APIs yet"
                    description="Publish categories, groups, and endpoints from the admin CMS to populate this explorer."
                />
            </div>
        @endforelse
    </div>
@endsection
