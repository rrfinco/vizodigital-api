@extends('layouts.docs')

@section('title', $entry->title . ' — Changelog — ' . config('portal.name'))

@section('content')
    <div class="mx-auto max-w-3xl">
        <nav class="mb-6 text-sm text-slate-500">
            <a href="{{ route('docs.overview') }}" class="hover:text-primary-600">Docs</a>
            <span class="mx-2">/</span>
            <a href="{{ route('docs.changelog.index', ['version' => $version->slug]) }}" class="hover:text-primary-600">Changelog</a>
            <span class="mx-2">/</span>
            <span class="text-slate-800 dark:text-slate-200">{{ $entry->title }}</span>
        </nav>

        @if ($entry->released_at)
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                {{ $entry->released_at->toFormattedDateString() }}
            </p>
        @endif

        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-portal-dark dark:text-white">
            {{ $entry->title }}
        </h1>

        <div class="mt-6">
            <x-docs.markdown :html="app(\App\Services\Rendering\MarkdownRenderer::class)->toHtml($entry->body_md)" />
        </div>
    </div>
@endsection
