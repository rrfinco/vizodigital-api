@extends('layouts.docs')

@section('title', 'Overview — ' . app(\App\Services\Portal\PortalSettings::class)->name())

@section('content')
    @php
        $settings = app(\App\Services\Portal\PortalSettings::class);
        $credentialsUrl = url('/user/credentials');
    @endphp

    <div class="mx-auto max-w-5xl">
        <div class="mb-10 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-sky-700 dark:text-sky-300">Developer docs</p>
                <h1 id="welcome" class="mt-2 text-3xl font-semibold tracking-tight text-[#0b1f3a] dark:text-white sm:text-4xl">
                    API Documentation
                </h1>
                <p class="mt-3 max-w-2xl text-base leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ $settings->tagline() ?: 'Complete reference for integrating Authentication, Recharge & Bill Payment APIs.' }}
                </p>
            </div>

            @if ($environment?->base_url)
                <div class="inline-flex items-center gap-2 self-start rounded-xl border border-sky-100 bg-sky-50 px-4 py-3 text-sm font-medium text-sky-900 dark:border-sky-900/40 dark:bg-sky-950/40 dark:text-sky-100">
                    <svg class="h-4 w-4 shrink-0 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.6 9h16.8M3.6 15h16.8M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18" />
                    </svg>
                    <span>Base URL: <span class="font-mono">{{ rtrim($environment->base_url, '/') }}/</span></span>
                </div>
            @endif
        </div>

        <section id="auth-first" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
            <div class="border-b border-slate-100 px-5 py-5 sm:px-6 dark:border-slate-800">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded-md bg-sky-100 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider text-sky-800 dark:bg-sky-950 dark:text-sky-200">Auth</span>
                    <h2 class="text-xl font-semibold text-[#0b1f3a] dark:text-white">Authentication</h2>
                </div>
                <p class="mt-3 max-w-3xl text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    Every business API requires a Bearer token. Exchange your client credentials once, then send
                    <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs dark:bg-slate-800">Authorization: Bearer {'{token}'}</code>
                    on Recharge and Bill Payment calls.
                </p>
            </div>

            <div class="flex flex-col gap-4 bg-slate-50/80 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:bg-slate-900/40">
                <div>
                    <p class="text-sm font-semibold text-[#0b1f3a] dark:text-white">Where to find your keys?</p>
                    <p class="mt-1 text-sm text-slate-500">
                        Manage client ID, API secret, and environment access from your developer panel.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($authPageUrl ?? null)
                        <a href="{{ $authPageUrl }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-900">
                            Authentication guide
                        </a>
                    @endif
                    <a href="{{ $credentialsUrl }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0b1f3a] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#132a4a] dark:bg-sky-600 dark:hover:bg-sky-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.174.1.347.218.52.35a1.14 1.14 0 0 1 .37 1.44l-.57.96a1.14 1.14 0 0 0 0 1.16l.57.96c.28.473.17 1.082-.27 1.44a8.7 8.7 0 0 1-.52.35c-.332.184-.582.496-.645.87l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.063-.374-.313-.686-.645-.87a8.7 8.7 0 0 1-.52-.35 1.14 1.14 0 0 1-.37-1.44l.57-.96a1.14 1.14 0 0 0 0-1.16l-.57-.96a1.14 1.14 0 0 1 .27-1.44c.17-.132.343-.25.52-.35.332-.184.582-.496.645-.87l.213-1.28Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        API Settings
                    </a>
                </div>
            </div>
        </section>

        @if (! $version)
            <div class="mt-10">
                <x-docs.empty-state
                    title="No published version yet"
                    description="Publish an API version in the admin CMS to unlock the explorer, FAQs, changelog, and SDK hub."
                />
            </div>
        @else
            <div id="context" class="mt-8 grid gap-4 sm:grid-cols-3">
                <a href="{{ $authPageUrl ?? route('docs.explorer', ['version' => $version->slug]) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-sky-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-950 dark:hover:border-sky-900">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">1 · Auth</p>
                    <p class="mt-2 text-base font-semibold text-[#0b1f3a] dark:text-white">Get a Bearer token</p>
                    <p class="mt-1 text-sm text-slate-500">Client credentials → access token</p>
                </a>
                <a href="{{ route('docs.explorer', ['version' => $version->slug]) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-sky-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-950 dark:hover:border-sky-900">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">2 · Endpoints</p>
                    <p class="mt-2 text-base font-semibold text-[#0b1f3a] dark:text-white">Recharge & Bill Pay</p>
                    <p class="mt-1 text-sm text-slate-500">Browse published business APIs</p>
                </a>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Context</p>
                    <p class="mt-2 text-base font-semibold text-[#0b1f3a] dark:text-white">{{ $version->name }}</p>
                    <p class="mt-1 break-all font-mono text-xs text-slate-500">{{ $environment?->base_url ?: '—' }}</p>
                </div>
            </div>
        @endif
    </div>
@endsection
