@extends('layouts.base')

@section('body')
    @php
        $portalEnvironment = $portalEnvironment ?? null;
        $portalEnvironments = $portalEnvironments ?? collect();
        $portalVersions = $portalVersions ?? collect();
        $portalVersion = $portalVersion ?? null;
        $portalNav = $portalNav ?? collect();
        $portalEnvironmentUrls = $portalEnvironmentUrls ?? collect();
        $portalVersionUrls = $portalVersionUrls ?? collect();
        $currentEnvSlug = $portalEnvironment?->slug instanceof \BackedEnum
            ? $portalEnvironment->slug->value
            : $portalEnvironment?->slug;
        $currentVersionSlug = $portalVersion?->slug;
        $portalSettings = app(\App\Services\Portal\PortalSettings::class);
        $baseUrl = $portalEnvironment?->base_url ?: config('app.url');
    @endphp
    <div class="docs-shell flex min-h-screen flex-col bg-[#f4f6f9] dark:bg-slate-950" x-data="{ sidebarOpen: false }">
        {{-- Top navbar --}}
        <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur-md dark:border-slate-800 dark:bg-slate-950/95">
            <div class="flex h-16 items-center gap-3 px-4 lg:px-8">
                <button
                    type="button"
                    class="rounded-lg border border-slate-200 p-2 text-slate-600 lg:hidden dark:border-slate-700 dark:text-slate-300"
                    @click="sidebarOpen = !sidebarOpen"
                    aria-label="Toggle sidebar"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <a href="{{ route('docs.overview') }}" class="flex min-w-0 items-center">
                    <img
                        src="{{ $portalSettings->logoUrl() }}"
                        alt="{{ $portalSettings->logoText() }}"
                        class="h-9 w-auto shrink-0 object-contain"
                    />
                </a>

                <div class="mx-auto hidden w-full max-w-md lg:block">
                    <x-docs.search :version-slug="$currentVersionSlug" />
                </div>

                <div class="ml-auto flex items-center gap-2">
                    @if ($baseUrl)
                        <div class="hidden items-center gap-2 rounded-xl border border-sky-100 bg-sky-50 px-3 py-2 text-xs font-medium text-sky-900 sm:inline-flex dark:border-sky-900/40 dark:bg-sky-950/40 dark:text-sky-100">
                            <svg class="h-3.5 w-3.5 shrink-0 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.6 9h16.8M3.6 15h16.8M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18" />
                            </svg>
                            <span class="truncate">Base URL: <span class="font-mono">{{ rtrim($baseUrl, '/') }}/</span></span>
                        </div>
                    @endif

                    @if (($portalEnvironments ?? collect())->isNotEmpty())
                        <label class="sr-only" for="portal-env-switcher">Environment</label>
                        <select
                            id="portal-env-switcher"
                            class="hidden rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-700 md:inline-flex dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
                            onchange="window.location.href = this.value"
                        >
                            @foreach ($portalEnvironments as $environment)
                                @php
                                    $slug = $environment->slug instanceof \BackedEnum ? $environment->slug->value : $environment->slug;
                                @endphp
                                <option
                                    value="{{ $portalEnvironmentUrls[$slug] ?? request()->fullUrlWithQuery(['env' => $slug]) }}"
                                    @selected($slug === $currentEnvSlug)
                                >
                                    {{ $environment->label ?: $environment->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                    @if (($portalVersions ?? collect())->isNotEmpty())
                        <label class="sr-only" for="portal-version-switcher">Version</label>
                        <select
                            id="portal-version-switcher"
                            class="hidden rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-700 md:inline-flex dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
                            onchange="window.location.href = this.value"
                        >
                            @foreach ($portalVersions as $version)
                                <option
                                    value="{{ $portalVersionUrls[$version->slug] ?? route('docs.overview') }}"
                                    @selected($version->slug === $currentVersionSlug)
                                >
                                    {{ $version->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                    <x-ui.theme-toggle />

                    @auth
                        <a
                            href="{{ url('/user') }}"
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#0b1f3a] text-xs font-semibold text-white dark:bg-sky-600"
                            title="{{ auth()->user()->name }}"
                        >
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-900">
                            Sign in
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <div class="relative flex flex-1">
            {{-- Mobile overlay --}}
            <div
                class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden"
                x-show="sidebarOpen"
                x-transition.opacity
                @click="sidebarOpen = false"
                style="display: none;"
            ></div>

            {{-- Left sidebar --}}
            <aside
                class="fixed inset-y-0 left-0 z-30 mt-16 w-64 shrink-0 overflow-y-auto border-r border-slate-200/80 bg-white px-3 py-5 transition-transform dark:border-slate-800 dark:bg-slate-950 lg:static lg:mt-0 lg:translate-x-0"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            >
                <nav class="space-y-5">
                    @if (($portalNav ?? collect())->isEmpty())
                        <div class="rounded-xl border border-dashed border-slate-200 p-3 text-xs text-slate-500 dark:border-slate-700">
                            No navigation yet. Publish content or add items in Admin → Navigation.
                        </div>
                    @else
                        @include('docs.partials.nav-nodes', ['nodes' => $portalNav ?? collect(), 'depth' => 0])
                    @endif
                </nav>
            </aside>

            {{-- Main --}}
            <div class="flex min-w-0 flex-1 flex-col">
                <main class="min-w-0 flex-1 px-4 py-8 sm:px-6 lg:px-10">
                    @yield('content')
                </main>

                <footer class="mt-auto border-t border-slate-200/80 bg-white px-4 py-5 sm:px-6 lg:px-10 dark:border-slate-800 dark:bg-slate-950">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-slate-500">
                            Copyright © {{ date('Y') }} {{ $portalSettings->name() }}. All rights reserved.
                        </p>
                        <div class="flex items-center gap-3 text-slate-400">
                            <span class="text-[11px] uppercase tracking-wider">Developer portal</span>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
    </div>
@endsection
