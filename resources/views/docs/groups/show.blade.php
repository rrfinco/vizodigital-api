@extends('layouts.docs')

@section('title', $group->name . ' — ' . config('portal.name'))

@section('content')
    <div class="mx-auto max-w-3xl">
        <nav class="mb-6 text-sm text-slate-500">
            <a href="{{ route('docs.overview') }}" class="hover:text-primary-600">Docs</a>
            <span class="mx-2">/</span>
            <a href="{{ route('docs.explorer', ['version' => $version->slug]) }}" class="hover:text-primary-600">{{ $version->name }}</a>
            @if ($group->category)
                <span class="mx-2">/</span>
                <a href="{{ route('docs.categories.show', ['version' => $version->slug, 'category' => $group->category->slug]) }}" class="hover:text-primary-600">
                    {{ $group->category->name }}
                </a>
            @endif
            <span class="mx-2">/</span>
            <span class="text-slate-800 dark:text-slate-200">{{ $group->name }}</span>
        </nav>

        <h1 class="text-3xl font-semibold tracking-tight text-portal-dark dark:text-white">{{ $group->name }}</h1>
        @if ($group->description)
            <p class="mt-3 text-base text-slate-600 dark:text-slate-300">{{ $group->description }}</p>
        @endif

        <ul class="mt-8 space-y-2">
            @forelse ($group->endpoints as $endpoint)
                <li>
                    <a
                        href="{{ route('docs.endpoints.show', ['version' => $version->slug, 'endpoint' => $endpoint->slug]) }}"
                        class="flex items-center gap-3 rounded-2xl border border-portal-border px-4 py-3 text-sm dark:border-slate-800"
                    >
                        <span class="rounded-xl bg-slate-900 px-2 py-0.5 font-mono text-[10px] font-semibold uppercase text-white dark:bg-slate-100 dark:text-slate-900">
                            {{ $endpoint->method?->value }}
                        </span>
                        <span>{{ $endpoint->name }}</span>
                        <code class="ml-auto hidden font-mono text-xs text-slate-400 sm:inline">{{ $endpoint->path }}</code>
                    </a>
                </li>
            @empty
                <li>
                    <x-docs.empty-state
                        title="No published endpoints"
                        description="Publish endpoints in this group to make them available in the portal."
                    />
                </li>
            @endforelse
        </ul>
    </div>
@endsection
