@extends('layouts.docs')

@section('title', ($document->preview ? '[Preview] ' : '') . $document->title . ' — ' . config('portal.name'))

@section('toc')
    @if ($document->toc->isNotEmpty())
        <ul class="space-y-2 text-sm">
            @foreach ($document->toc as $item)
                <li>
                    <a href="#{{ $item->anchor }}" class="text-slate-600 hover:text-primary-600 dark:text-slate-300 dark:hover:text-primary-400">
                        {{ $item->label }}
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-sm text-slate-400">No sections on this page.</p>
    @endif
@endsection

@section('content')
    <div class="mx-auto max-w-3xl">
        @if ($document->preview)
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100">
                <strong>Preview mode</strong>
                — status <span class="font-mono">{{ $document->status?->value }}</span>.
            </div>
        @endif

        <nav class="mb-6 text-sm text-slate-500">
            <a href="{{ route('docs.overview') }}" class="hover:text-primary-600">Docs</a>
            <span class="mx-2">/</span>
            <span class="text-slate-800 dark:text-slate-200">{{ $document->versionName ?? $document->versionSlug }}</span>
            <span class="mx-2">/</span>
            <span class="text-slate-800 dark:text-slate-200">{{ $document->title }}</span>
        </nav>

        @if ($document->type)
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $document->type->value }}</p>
        @endif

        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-portal-dark dark:text-white">
            {{ $document->title }}
        </h1>

        @if ($document->bodyHtml)
            <div id="overview" class="mt-6">
                <x-docs.markdown :html="$document->bodyHtml" />
            </div>
        @endif

        @foreach ($document->blocks as $block)
            <section id="{{ $block->anchor }}" class="mt-10">
                @if ($block->title)
                    <h2 class="text-lg font-semibold text-portal-dark dark:text-white">{{ $block->title }}</h2>
                @endif
                <div class="mt-3">
                    <x-docs.markdown :html="$block->bodyHtml" />
                </div>
            </section>
        @endforeach
    </div>
@endsection
