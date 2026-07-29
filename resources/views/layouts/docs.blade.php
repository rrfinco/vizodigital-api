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
    @endphp
    <div class="flex min-h-screen flex-col" x-data="{ sidebarOpen: false }">
        {{-- Top navbar --}}
        <header class="sticky top-0 z-40 border-b border-portal-border bg-white/90 backdrop-blur-md dark:border-slate-800 dark:bg-slate-950/90">
            <div class="flex h-14 items-center gap-3 px-4 lg:px-6">
                <button
                    type="button"
                    class="rounded-2xl border border-portal-border p-2 text-slate-600 lg:hidden dark:border-slate-700 dark:text-slate-300"
                    @click="sidebarOpen = !sidebarOpen"
                    aria-label="Toggle sidebar"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <a href="{{ route('landing') }}" class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-2xl bg-primary-600 text-xs font-semibold text-white">
                        {{ strtoupper(substr(app(\App\Services\Portal\PortalSettings::class)->logoText(), 0, 1)) }}
                    </span>
                    <span class="hidden text-sm font-semibold sm:inline">{{ app(\App\Services\Portal\PortalSettings::class)->logoText() }}</span>
                </a>

                <div class="mx-auto hidden w-full max-w-md md:block">
                    <x-docs.search :version-slug="$currentVersionSlug" />
                </div>

                <div class="ml-auto flex items-center gap-2">
                    @if (($portalEnvironments ?? collect())->isNotEmpty())
                        <label class="sr-only" for="portal-env-switcher">Environment</label>
                        <select
                            id="portal-env-switcher"
                            class="hidden rounded-2xl border border-portal-border bg-white px-3 py-1.5 text-xs text-slate-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 sm:inline-flex"
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
                            class="hidden rounded-2xl border border-portal-border bg-white px-3 py-1.5 text-xs text-slate-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 sm:inline-flex"
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
                    <button type="button" class="rounded-2xl border border-portal-border p-2 text-slate-400 dark:border-slate-700" disabled title="Notifications — later">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                    </button>
                    @auth
                        <a
                            href="{{ route('profile') }}"
                            class="flex h-8 w-8 items-center justify-center rounded-2xl bg-primary-50 text-xs font-semibold text-primary-700 dark:bg-primary-950/50 dark:text-primary-300"
                            title="{{ auth()->user()->name }}"
                        >
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                            @csrf
                            <button type="submit" class="rounded-2xl border border-portal-border px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-900">
                                Log out
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="rounded-2xl border border-portal-border px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-900">
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
                class="fixed inset-y-0 left-0 z-30 mt-14 w-64 shrink-0 overflow-y-auto border-r border-portal-border bg-white px-3 py-4 transition-transform dark:border-slate-800 dark:bg-slate-950 lg:static lg:mt-0 lg:translate-x-0"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            >
                <p class="mb-3 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Documentation</p>
                <nav class="space-y-0.5">
                    @if (($portalNav ?? collect())->isEmpty())
                        <div class="rounded-2xl border border-dashed border-portal-border p-3 text-xs text-slate-500 dark:border-slate-700">
                            No navigation yet. Publish content or add items in Admin → Navigation.
                        </div>
                    @else
                        @include('docs.partials.nav-nodes', ['nodes' => $portalNav ?? collect(), 'depth' => 0])
                    @endif
                </nav>
            </aside>

            {{-- Main + right --}}
            <div class="flex min-w-0 flex-1">
                <main class="min-w-0 flex-1 px-4 py-8 sm:px-6 lg:px-10">
                    @yield('content')
                </main>

                <aside class="hidden w-56 shrink-0 border-l border-portal-border px-4 py-8 xl:block dark:border-slate-800">
                    <p class="mb-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">On this page</p>
                    @hasSection('toc')
                        @yield('toc')
                    @else
                        <p class="text-sm text-slate-400">Table of contents appears when content is published.</p>
                    @endif

                    <div class="mt-8">
                        <p class="mb-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Related APIs</p>
                        @hasSection('related')
                            @yield('related')
                        @else
                            <p class="text-sm text-slate-400">Related endpoints appear on API pages.</p>
                        @endif
                    </div>

                    <div class="mt-8">
                        <p class="mb-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Environment</p>
                        @if ($portalEnvironment)
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                {{ $portalEnvironment->label ?: $portalEnvironment->name }}
                            </p>
                            <p class="mt-1 break-all font-mono text-xs text-slate-500">
                                {{ $portalEnvironment->base_url }}
                            </p>
                            <ul class="mt-3 space-y-1.5 text-sm">
                                @foreach ($portalEnvironments as $environment)
                                    @php
                                        $slug = $environment->slug instanceof \BackedEnum ? $environment->slug->value : $environment->slug;
                                    @endphp
                                    <li>
                                        <a
                                            href="{{ $portalEnvironmentUrls[$slug] ?? request()->fullUrlWithQuery(['env' => $slug]) }}"
                                            @class([
                                                'hover:text-primary-600 dark:hover:text-primary-400',
                                                'font-medium text-primary-700 dark:text-primary-300' => $slug === $currentEnvSlug,
                                                'text-slate-500' => $slug !== $currentEnvSlug,
                                            ])
                                        >
                                            {{ $environment->badge ?: $environment->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-sm text-slate-400">No environments configured.</p>
                        @endif
                    </div>
                </aside>
            </div>
        </div>
    </div>
@endsection
