@extends('layouts.docs')

@section('title', 'SDKs — ' . $version->name . ' — ' . config('portal.name'))

@section('content')
    <div class="mx-auto max-w-3xl">
        <nav class="mb-6 text-sm text-slate-500">
            <a href="{{ route('docs.overview') }}" class="hover:text-primary-600">Docs</a>
            <span class="mx-2">/</span>
            <span class="text-slate-800 dark:text-slate-200">{{ $version->name }}</span>
            <span class="mx-2">/</span>
            <span class="text-slate-800 dark:text-slate-200">SDKs</span>
        </nav>

        <h1 class="text-3xl font-semibold tracking-tight text-portal-dark dark:text-white">SDK hub</h1>
        <p class="mt-3 text-base text-slate-600 dark:text-slate-300">
            Official and community packages for {{ $version->name }}.
        </p>

        <div class="mt-8 space-y-4">
            @forelse ($packages as $package)
                <article class="rounded-2xl border border-portal-border px-4 py-4 dark:border-slate-800">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base font-semibold text-portal-dark dark:text-white">{{ $package->name }}</h2>
                        <span class="rounded-lg bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                            {{ $package->language?->label() ?? $package->language?->value }}
                        </span>
                    </div>
                    @if ($package->package_name)
                        <p class="mt-1 font-mono text-xs text-slate-500">{{ $package->package_name }}</p>
                    @endif
                    @if ($package->repo_url)
                        <a href="{{ $package->repo_url }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex text-xs font-medium text-primary-600 hover:text-primary-700">
                            Repository →
                        </a>
                    @endif
                    @if ($package->install_md)
                        <div class="mt-4">
                            <x-docs.markdown :html="app(\App\Services\Rendering\MarkdownRenderer::class)->toHtml($package->install_md)" />
                        </div>
                    @endif
                </article>
            @empty
                <x-docs.empty-state
                    title="No published SDK packages yet"
                    description="Add SDK packages in the admin CMS and publish them to list install instructions here."
                />
            @endforelse
        </div>
    </div>
@endsection
