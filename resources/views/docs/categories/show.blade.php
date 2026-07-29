@extends('layouts.docs')

@section('title', $category->name . ' — ' . config('portal.name'))

@section('content')
    <div class="mx-auto max-w-3xl">
        <nav class="mb-6 text-sm text-slate-500">
            <a href="{{ route('docs.overview') }}" class="hover:text-primary-600">Docs</a>
            <span class="mx-2">/</span>
            <a href="{{ route('docs.explorer', ['version' => $version->slug]) }}" class="hover:text-primary-600">{{ $version->name }}</a>
            <span class="mx-2">/</span>
            <span class="text-slate-800 dark:text-slate-200">{{ $category->name }}</span>
        </nav>

        <h1 class="text-3xl font-semibold tracking-tight text-portal-dark dark:text-white">{{ $category->name }}</h1>
        @if ($category->description)
            <p class="mt-3 text-base text-slate-600 dark:text-slate-300">{{ $category->description }}</p>
        @endif

        @forelse ($category->groups as $group)
            <section class="mt-10">
                <h2 class="text-lg font-semibold text-portal-dark dark:text-white">
                    <a href="{{ route('docs.groups.show', ['version' => $version->slug, 'group' => $group->slug]) }}" class="hover:text-primary-600">
                        {{ $group->name }}
                    </a>
                </h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($group->endpoints as $endpoint)
                        <li>
                            <a
                                href="{{ route('docs.endpoints.show', ['version' => $version->slug, 'endpoint' => $endpoint->slug]) }}"
                                class="flex items-center gap-3 rounded-2xl border border-portal-border px-4 py-3 text-sm dark:border-slate-800"
                            >
                                <span class="rounded-xl bg-slate-900 px-2 py-0.5 font-mono text-[10px] font-semibold uppercase text-white dark:bg-slate-100 dark:text-slate-900">
                                    {{ $endpoint->method?->value }}
                                </span>
                                {{ $endpoint->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <div class="mt-8">
                <x-docs.empty-state
                    title="No published groups"
                    description="Publish groups under this category to list their endpoints."
                />
            </div>
        @endforelse
    </div>
@endsection
