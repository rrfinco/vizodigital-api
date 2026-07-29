@extends('layouts.docs')

@section('title', 'Changelog — ' . $version->name . ' — ' . config('portal.name'))

@section('content')
    <div class="mx-auto max-w-3xl">
        <nav class="mb-6 text-sm text-slate-500">
            <a href="{{ route('docs.overview') }}" class="hover:text-primary-600">Docs</a>
            <span class="mx-2">/</span>
            <span class="text-slate-800 dark:text-slate-200">{{ $version->name }}</span>
            <span class="mx-2">/</span>
            <span class="text-slate-800 dark:text-slate-200">Changelog</span>
        </nav>

        <h1 class="text-3xl font-semibold tracking-tight text-portal-dark dark:text-white">Changelog</h1>
        <p class="mt-3 text-base text-slate-600 dark:text-slate-300">
            Release notes for {{ $version->name }}.
        </p>

        <ul class="mt-8 space-y-4">
            @forelse ($entries as $entry)
                <li class="rounded-2xl border border-portal-border px-4 py-4 dark:border-slate-800">
                    <a href="{{ route('docs.changelog.show', ['version' => $version->slug, 'entry' => $entry->slug]) }}" class="block">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-semibold text-portal-dark hover:text-primary-600 dark:text-white">{{ $entry->title }}</h2>
                            @if ($entry->released_at)
                                <span class="text-xs text-slate-400">{{ $entry->released_at->toFormattedDateString() }}</span>
                            @endif
                        </div>
                        @if ($entry->body_md)
                            <p class="mt-2 line-clamp-2 text-sm text-slate-500">{{ \Illuminate\Support\Str::limit(strip_tags(\Illuminate\Support\Str::markdown($entry->body_md)), 160) }}</p>
                        @endif
                    </a>
                </li>
            @empty
                <li>
                    <x-docs.empty-state
                        title="No published changelog entries yet"
                        description="Publish release notes from the admin CMS to populate this list."
                    />
                </li>
            @endforelse
        </ul>
    </div>
@endsection
