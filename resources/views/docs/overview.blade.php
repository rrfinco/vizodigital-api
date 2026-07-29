@extends('layouts.docs')

@section('title', 'Overview — ' . app(\App\Services\Portal\PortalSettings::class)->name())

@section('toc')
    <ul class="space-y-2 text-sm">
        <li><a href="#welcome" class="text-slate-600 hover:text-primary-600 dark:text-slate-300 dark:hover:text-primary-400">Welcome</a></li>
        <li><a href="#auth-first" class="text-slate-600 hover:text-primary-600 dark:text-slate-300 dark:hover:text-primary-400">Authenticate first</a></li>
        <li><a href="#context" class="text-slate-600 hover:text-primary-600 dark:text-slate-300 dark:hover:text-primary-400">Context</a></li>
    </ul>
@endsection

@section('content')
    <div class="mx-auto max-w-3xl">
        <nav class="mb-6 text-sm text-slate-500">
            <a href="{{ route('landing') }}" class="hover:text-primary-600">Home</a>
            <span class="mx-2">/</span>
            <span class="text-slate-800 dark:text-slate-200">Overview</span>
        </nav>

        <h1 id="welcome" class="text-3xl font-semibold tracking-tight text-portal-dark dark:text-white">
            Overview
        </h1>
        <p class="mt-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
            {{ app(\App\Services\Portal\PortalSettings::class)->tagline() }}
            Browse published API documentation by version and environment. Content is managed from the admin CMS — nothing is hardcoded here.
        </p>

        <section id="auth-first" class="mt-10 rounded-2xl border border-primary-200 bg-primary-50/70 p-5 dark:border-primary-900/50 dark:bg-primary-950/30">
            <h2 class="text-lg font-semibold text-portal-dark dark:text-white">1. Authenticate before calling APIs</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                Every integration starts with system authentication. Sign up, complete KYC, wait for admin approval, then use your UAT client credentials to obtain a Bearer token. Business APIs will reject calls without a valid token for that environment.
            </p>
            <ol class="mt-4 list-decimal space-y-2 ps-5 text-sm text-slate-600 dark:text-slate-300">
                <li>Create an account and complete KYC from the email link</li>
                <li>After approval, open the developer panel → <strong>API Keys</strong> for UAT credentials</li>
                <li>Exchange credentials: <code class="rounded bg-white/80 px-1.5 py-0.5 font-mono text-xs dark:bg-slate-900">POST /api/v1/auth/client-credentials</code></li>
                <li>Send <code class="rounded bg-white/80 px-1.5 py-0.5 font-mono text-xs dark:bg-slate-900">Authorization: Bearer {'{token}'}</code> on subsequent API calls</li>
                <li>Production keys appear only after an admin unlocks live access</li>
            </ol>
            @if ($authPageUrl ?? null)
                <a href="{{ $authPageUrl }}" class="mt-4 inline-flex text-sm font-medium text-primary-700 hover:text-primary-800 dark:text-primary-300">
                    Open Authentication guide →
                </a>
            @endif
        </section>

        @if (! $version)
            <div class="mt-10">
                <x-docs.empty-state
                    title="No published version yet"
                    description="Publish an API version in the admin CMS to unlock the explorer, FAQs, changelog, and SDK hub."
                />
            </div>
        @else
            <div id="context" class="mt-8 grid gap-4 sm:grid-cols-2">
                <div class="portal-card p-5 dark:border-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Environment</p>
                    <p class="mt-1 text-sm font-medium">{{ $environment?->label ?: $environment?->name ?: '—' }}</p>
                    <p class="mt-1 break-all font-mono text-xs text-slate-500">{{ $environment?->base_url }}</p>
                </div>
                <div class="portal-card p-5 dark:border-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Version</p>
                    <p class="mt-1 text-sm font-medium">{{ $version->name }}</p>
                    <a
                        href="{{ route('docs.explorer', ['version' => $version->slug]) }}"
                        class="mt-2 inline-flex text-xs font-medium text-primary-600 hover:text-primary-700"
                    >
                        Open API explorer →
                    </a>
                </div>
            </div>
        @endif
    </div>
@endsection
