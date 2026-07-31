@extends('layouts.landing')

@section('title', config('portal.name') . ' — ' . config('portal.tagline'))

@section('content')
    <section class="relative overflow-hidden">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,_#2563EB22,_transparent_55%),linear-gradient(to_bottom,#F8FAFC,#EEF2FF)] dark:bg-[radial-gradient(ellipse_at_top,_#2563EB33,_transparent_55%),linear-gradient(to_bottom,#020617,#0f172a)]"></div>

        <div class="relative mx-auto grid max-w-7xl gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:items-center lg:gap-16 lg:px-8 lg:py-28">
            <div class="animate-in fade-in duration-700">
                <p class="mb-4 text-sm font-semibold tracking-wide text-primary-600 dark:text-primary-400">
                    {{ config('portal.name') }}
                </p>
                <img
                    src="{{ asset(config('portal.brand.logo')) }}"
                    alt="{{ config('portal.brand.logo_text') }}"
                    class="h-14 w-auto object-contain sm:h-16"
                />
                <p class="mt-4 max-w-lg text-lg text-slate-600 dark:text-slate-300">
                    {{ config('portal.tagline') }}
                </p>
                <p class="mt-3 max-w-lg text-sm text-slate-500 dark:text-slate-400">
                    A CMS-driven documentation portal. APIs, examples, and guides will be managed from the admin panel — nothing is hardcoded.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('docs.overview') }}" class="portal-btn-primary">
                        Get Started
                    </a>
                    <a href="{{ route('docs.overview') }}" class="portal-btn-secondary">
                        View Documentation
                    </a>
                </div>
            </div>

            <div class="portal-card overflow-hidden dark:border-slate-800">
                <div class="flex items-center gap-2 border-b border-portal-border px-4 py-3 dark:border-slate-800">
                    <span class="h-2.5 w-2.5 rounded-full bg-portal-danger/80"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-portal-warning/80"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-portal-success/80"></span>
                    <span class="ml-2 text-xs text-slate-400">example.http — placeholder</span>
                </div>
                <pre class="overflow-x-auto bg-slate-950 p-5 text-sm leading-relaxed text-slate-200"><code><span class="text-emerald-400">GET</span> {{ config('portal.environments.uat.base_url') }}/v1/...
<span class="text-slate-500">Authorization:</span> Bearer <span class="text-amber-300">YOUR_API_KEY</span>
<span class="text-slate-500">Accept:</span> application/json

<span class="text-slate-500"># Published endpoints will appear here from the CMS.</span></code></pre>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid gap-6 md:grid-cols-3">
            @foreach ([
                ['title' => 'Dynamic docs', 'body' => 'Categories, groups, and endpoints are stored in the database and rendered automatically.'],
                ['title' => 'UAT & Production', 'body' => 'Environment switcher will rebind base URLs, samples, and collections per environment.'],
                ['title' => 'Admin CMS', 'body' => 'Filament admin will let you publish unlimited APIs without changing application code.'],
            ] as $feature)
                <div class="portal-card p-6 dark:border-slate-800">
                    <h2 class="text-base font-semibold text-portal-dark dark:text-white">{{ $feature['title'] }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $feature['body'] }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endsection
