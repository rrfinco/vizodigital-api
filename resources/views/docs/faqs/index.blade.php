@extends('layouts.docs')

@section('title', 'FAQ — ' . $version->name . ' — ' . config('portal.name'))

@section('toc')
    <ul class="space-y-2 text-sm">
        @foreach ($groups as $category => $items)
            <li>
                <a href="#{{ \Illuminate\Support\Str::slug($category) }}" class="text-slate-600 hover:text-primary-600 dark:text-slate-300 dark:hover:text-primary-400">
                    {{ $category }}
                </a>
            </li>
        @endforeach
    </ul>
@endsection

@section('content')
    <div class="mx-auto max-w-3xl">
        <nav class="mb-6 text-sm text-slate-500">
            <a href="{{ route('docs.overview') }}" class="hover:text-primary-600">Docs</a>
            <span class="mx-2">/</span>
            <span class="text-slate-800 dark:text-slate-200">{{ $version->name }}</span>
            <span class="mx-2">/</span>
            <span class="text-slate-800 dark:text-slate-200">FAQ</span>
        </nav>

        <h1 class="text-3xl font-semibold tracking-tight text-portal-dark dark:text-white">FAQ</h1>
        <p class="mt-3 text-base text-slate-600 dark:text-slate-300">
            Frequently asked questions for {{ $version->name }}.
        </p>

        @forelse ($groups as $category => $items)
            <section id="{{ \Illuminate\Support\Str::slug($category) }}" class="mt-10">
                <h2 class="text-lg font-semibold text-portal-dark dark:text-white">{{ $category }}</h2>
                <div class="mt-4 space-y-4">
                    @foreach ($items as $faq)
                        <article id="faq-{{ $faq->id }}" class="rounded-2xl border border-portal-border px-4 py-4 dark:border-slate-800">
                            <h3 class="text-sm font-semibold text-portal-dark dark:text-white">{{ $faq->question }}</h3>
                            <div class="mt-2">
                                <x-docs.markdown :html="app(\App\Services\Rendering\MarkdownRenderer::class)->toHtml($faq->answer_md)" />
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="mt-10">
                <x-docs.empty-state
                    title="No published FAQs yet"
                    description="Add and publish FAQ entries in the admin CMS to show them here."
                />
            </div>
        @endforelse
    </div>
@endsection
