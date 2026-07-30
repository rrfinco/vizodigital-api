@extends('layouts.docs')

@section('title', ($document->preview ? '[Preview] ' : '') . $document->title . ' — ' . config('portal.name'))

@section('content')
    @php
        $isAuth = strtolower((string) ($document->type?->value ?? '')) === 'authentication'
            || str_contains(strtolower($document->title), 'auth');
        $credentialsUrl = url('/user/credentials');
    @endphp

    <div class="mx-auto max-w-4xl">
        @if ($document->preview)
            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100">
                <strong>Preview mode</strong>
                — status <span class="font-mono">{{ $document->status?->value }}</span>.
            </div>
        @endif

        <div class="mb-6 flex flex-wrap items-center gap-3">
            @if ($isAuth)
                <span class="rounded-md bg-sky-100 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider text-sky-800 dark:bg-sky-950 dark:text-sky-200">Auth</span>
            @elseif ($document->type)
                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $document->type->value }}</span>
            @endif
        </div>

        <h1 class="text-3xl font-semibold tracking-tight text-[#0b1f3a] dark:text-white">
            {{ $document->title }}
        </h1>

        @if ($isAuth)
            <p class="mt-3 max-w-3xl text-base leading-relaxed text-slate-600 dark:text-slate-300">
                Obtain a Bearer token with your client credentials before calling Recharge or Bill Payment APIs.
            </p>

            <div class="mt-6 flex flex-col gap-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm sm:flex-row sm:items-center sm:justify-between dark:border-slate-800 dark:bg-slate-950">
                <div class="px-5 py-4">
                    <p class="text-sm font-semibold text-[#0b1f3a] dark:text-white">Where to find your keys?</p>
                    <p class="mt-1 text-sm text-slate-500">
                        Open the developer panel to copy Client ID, API Secret, and manage environment access.
                    </p>
                </div>
                <div class="border-t border-slate-100 px-5 py-4 sm:border-l sm:border-t-0 dark:border-slate-800">
                    <a href="{{ $credentialsUrl }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0b1f3a] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#132a4a] dark:bg-sky-600 dark:hover:bg-sky-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.174.1.347.218.52.35a1.14 1.14 0 0 1 .37 1.44l-.57.96a1.14 1.14 0 0 0 0 1.16l.57.96c.28.473.17 1.082-.27 1.44a8.7 8.7 0 0 1-.52.35c-.332.184-.582.496-.645.87l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.063-.374-.313-.686-.645-.87a8.7 8.7 0 0 1-.52-.35 1.14 1.14 0 0 1-.37-1.44l.57-.96a1.14 1.14 0 0 0 0-1.16l-.57-.96a1.14 1.14 0 0 1 .27-1.44c.17-.132.343-.25.52-.35.332-.184.582-.496.645-.87l.213-1.28Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        API Settings
                    </a>
                </div>
            </div>
        @endif

        @if ($document->bodyHtml)
            <div id="overview" class="mt-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-800 dark:bg-slate-950">
                <x-docs.markdown :html="$document->bodyHtml" />
            </div>
        @endif

        @foreach ($document->blocks as $block)
            <section id="{{ $block->anchor }}" class="mt-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-800 dark:bg-slate-950">
                @if ($block->title)
                    <h2 class="text-lg font-semibold text-[#0b1f3a] dark:text-white">{{ $block->title }}</h2>
                @endif
                <div class="mt-3">
                    <x-docs.markdown :html="$block->bodyHtml" />
                </div>
            </section>
        @endforeach
    </div>
@endsection
