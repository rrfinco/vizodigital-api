@extends('layouts.docs')

@section('title', ($document->preview ? '[Preview] ' : '') . $document->name . ' — ' . config('portal.name'))

@section('toc')
    <ul class="space-y-2 text-sm">
        @foreach ($document->toc as $item)
            <li>
                <a href="#{{ $item->anchor }}" class="text-slate-600 hover:text-primary-600 dark:text-slate-300 dark:hover:text-primary-400">
                    {{ $item->label }}
                </a>
            </li>
        @endforeach
    </ul>
@endsection

@section('related')
    @if ($document->related->isNotEmpty())
        <ul class="space-y-2 text-sm">
            @foreach ($document->related as $related)
                <li>
                    <a href="{{ $related->url }}" class="text-slate-600 hover:text-primary-600 dark:text-slate-300 dark:hover:text-primary-400">
                        <span class="font-mono text-[10px] uppercase text-slate-400">{{ $related->method?->value }}</span>
                        {{ $related->label ?: $related->name }}
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-sm text-slate-400">No related APIs linked.</p>
    @endif
@endsection

@section('content')
    <div class="mx-auto max-w-3xl">
        @if ($document->preview)
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100">
                <strong>Preview mode</strong>
                — status <span class="font-mono">{{ $document->status?->value }}</span>.
                Drafts are not visible on the public docs until published.
            </div>
        @endif

        <nav class="mb-6 text-sm text-slate-500">
            <a href="{{ route('docs.overview') }}" class="hover:text-primary-600">Docs</a>
            <span class="mx-2">/</span>
            <span class="text-slate-800 dark:text-slate-200">{{ $document->categoryName ?? 'API' }}</span>
            @if ($document->groupName)
                <span class="mx-2">/</span>
                <span class="text-slate-800 dark:text-slate-200">{{ $document->groupName }}</span>
            @endif
            <span class="mx-2">/</span>
            <span class="text-slate-800 dark:text-slate-200">{{ $document->name }}</span>
        </nav>

        <div class="flex flex-wrap items-center gap-3">
            <span class="rounded-2xl bg-slate-900 px-2.5 py-1 font-mono text-xs font-semibold uppercase text-white dark:bg-slate-100 dark:text-slate-900">
                {{ $document->method?->value }}
            </span>
            <code class="rounded-2xl bg-slate-100 px-3 py-1 font-mono text-sm text-slate-800 dark:bg-slate-900 dark:text-slate-100">
                {{ $document->path }}
            </code>
        </div>

        @if ($document->baseUrl)
            <p class="mt-3 font-mono text-xs text-slate-500">
                Base URL ({{ $document->environmentName }}):
                <span class="text-slate-700 dark:text-slate-300">{{ $document->baseUrl }}</span>
            </p>
        @endif

        <h1 id="summary" class="mt-4 text-3xl font-semibold tracking-tight text-portal-dark dark:text-white">
            {{ $document->name }}
        </h1>

        @if ($document->summary)
            <p class="mt-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ $document->summary }}
            </p>
        @endif

        @foreach ($document->sections as $section)
            {!! $renderer->render($section) !!}
        @endforeach

        <section id="meta" class="mt-10 grid gap-4 sm:grid-cols-2">
            <div class="portal-card p-5 dark:border-slate-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Publish status</p>
                <p class="mt-1 text-sm font-medium capitalize">{{ $document->status?->value }}</p>
                @if ($document->publishedAt)
                    <p class="mt-1 text-xs text-slate-500">Published {{ $document->publishedAt }}</p>
                @endif
            </div>
            <div class="portal-card p-5 dark:border-slate-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Version</p>
                <p class="mt-1 text-sm font-medium">{{ $document->versionName ?? $document->versionSlug }}</p>
                @if ($document->environmentName)
                    <p class="mt-1 text-xs text-slate-500">Environment: {{ $document->environmentName }}</p>
                @endif
            </div>
        </section>
    </div>
@endsection
