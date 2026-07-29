@extends('layouts.base')

@section('body')
    <div class="min-h-screen">
        <header class="sticky top-0 z-40 border-b border-portal-border/80 bg-white/80 backdrop-blur-md dark:border-slate-800 dark:bg-slate-950/80">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('landing') }}" class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-2xl bg-primary-600 text-sm font-semibold text-white shadow-card">
                        {{ strtoupper(substr(app(\App\Services\Portal\PortalSettings::class)->logoText(), 0, 1)) }}
                    </span>
                    <span class="text-base font-semibold tracking-tight text-portal-dark dark:text-white">
                        {{ app(\App\Services\Portal\PortalSettings::class)->logoText() }}
                    </span>
                </a>

                <nav class="hidden items-center gap-6 text-sm text-slate-600 dark:text-slate-300 md:flex">
                    <a href="{{ route('docs.overview') }}" class="hover:text-primary-600 dark:hover:text-primary-400">Documentation</a>
                    @if ($defaultVersion ?? null)
                        <a href="{{ route('docs.explorer', ['version' => $defaultVersion->slug]) }}" class="hover:text-primary-600 dark:hover:text-primary-400">API Explorer</a>
                        <a href="{{ route('docs.faqs.index', ['version' => $defaultVersion->slug]) }}" class="hover:text-primary-600 dark:hover:text-primary-400">Guides & FAQ</a>
                    @else
                        <span class="text-slate-400">API Explorer</span>
                        <span class="text-slate-400">Guides & FAQ</span>
                    @endif
                </nav>

                <div class="flex items-center gap-2">
                    <x-ui.theme-toggle />
                    @auth
                        <a href="{{ route('profile') }}" class="hidden text-sm text-slate-600 hover:text-primary-600 sm:inline dark:text-slate-300">
                            {{ auth()->user()->name }}
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="portal-btn-secondary">Log out</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="portal-btn-secondary hidden sm:inline-flex">Sign in</a>
                        <a href="{{ route('register') }}" class="portal-btn-secondary hidden sm:inline-flex">Sign up</a>
                        <a href="{{ route('docs.overview') }}" class="portal-btn-primary hidden sm:inline-flex">
                            View Documentation
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <main>
            @yield('content')
        </main>

        <footer class="border-t border-portal-border py-10 dark:border-slate-800">
            <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 text-sm text-slate-500 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <p>{{ app(\App\Services\Portal\PortalSettings::class)->logoText() }} — Documentation management infrastructure</p>
                <p>{{ app(\App\Services\Portal\PortalSettings::class)->tagline() }}</p>
            </div>
        </footer>
    </div>
@endsection
