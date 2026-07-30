@extends('layouts.docs')

@section('title', ($document->preview ? '[Preview] ' : '') . $document->name . ' — ' . config('portal.name'))

@section('content')
    @php
        $docSections = ['overview', 'headers', 'parameters', 'body', 'responses', 'errors', 'notes', 'rate_limits', 'permissions', 'webhooks'];
        $leftSections = $document->sections->filter(fn ($s) => in_array($s->key->value, $docSections, true));
        $examplesSection = $document->sections->first(fn ($s) => $s->key->value === 'examples');
        $responsesSection = $document->sections->first(fn ($s) => $s->key->value === 'responses');
        $sdkSection = $document->sections->first(fn ($s) => $s->key->value === 'sdk');

        $examples = collect($examplesSection->data['examples'] ?? []);
        $responses = collect($responsesSection->data['responses'] ?? []);
        $codeSamples = collect($sdkSection->data['samples'] ?? []);
        $bodies = collect(
            $document->sections->first(fn ($s) => $s->key->value === 'body')?->data['bodies'] ?? []
        );

        $successExample = $examples->first(function ($example) {
            $status = (int) ($example->response_status ?? 0);
            $title = strtolower((string) ($example->title ?? ''));

            return ($status >= 200 && $status < 300) || str_contains($title, 'success');
        }) ?? $examples->first();

        $failureExample = $examples->first(function ($example) use ($successExample) {
            if ($successExample && $example === $successExample) {
                return false;
            }
            $status = (int) ($example->response_status ?? 0);
            $title = strtolower((string) ($example->title ?? ''));

            return $status >= 400 || str_contains($title, 'fail') || str_contains($title, 'error') || str_contains($title, 'duplicate');
        });

        $successResponse = $responses->first(fn ($r) => (int) $r->status_code >= 200 && (int) $r->status_code < 300);
        $failureResponse = $responses->first(fn ($r) => (int) $r->status_code >= 400);

        $requestPayload = $successExample?->request
            ?? $bodies->first()?->example
            ?? null;

        $method = strtoupper($document->method?->value ?? 'POST');
        $fullUrl = rtrim((string) $document->baseUrl, '/').$document->path;
        $methodTone = match ($method) {
            'GET' => 'text-emerald-400',
            'POST' => 'text-violet-300',
            'PUT' => 'text-sky-300',
            'PATCH' => 'text-amber-300',
            'DELETE' => 'text-rose-300',
            default => 'text-slate-300',
        };
    @endphp

    <div class="mx-auto max-w-6xl" x-data>
        @if ($document->preview)
            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100">
                <strong>Preview mode</strong>
                — status <span class="font-mono">{{ $document->status?->value }}</span>.
            </div>
        @endif

        <div class="mb-8">
            <p class="text-xs font-medium uppercase tracking-[0.14em] text-slate-400">
                {{ $document->categoryName ?? 'API' }}
                @if ($document->groupName)
                    · {{ $document->groupName }}
                @endif
            </p>
            <h1 id="summary" class="mt-2 text-3xl font-semibold tracking-tight text-[#0b1f3a] dark:text-white">
                {{ $document->name }}
            </h1>
            @if ($document->summary)
                <p class="mt-3 max-w-3xl text-base leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ $document->summary }}
                </p>
            @endif
        </div>

        <div class="grid gap-5 xl:grid-cols-2">
            {{-- Request cards --}}
            <div class="space-y-4">
                <div class="overflow-hidden rounded-2xl bg-[#0b1f3a] text-slate-100 shadow-lg shadow-slate-900/10">
                    <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                        <p @class(['text-[11px] font-bold uppercase tracking-[0.16em]', $methodTone])>
                            {{ $method }} request
                        </p>
                        <button
                            type="button"
                            class="rounded-md px-2 py-1 text-[11px] font-medium text-slate-300 hover:bg-white/10 hover:text-white"
                            @click="navigator.clipboard.writeText(@js($fullUrl))"
                        >
                            Copy
                        </button>
                    </div>
                    <div class="space-y-3 p-4">
                        <p class="break-all font-mono text-xs leading-relaxed text-sky-100/90">
                            <span class="text-slate-400">URL:</span> {{ $fullUrl }}
                        </p>
                        @if ($requestPayload)
                            <div>
                                <p class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Body</p>
                                <pre class="overflow-x-auto rounded-xl bg-[#071528] p-3 font-mono text-xs leading-relaxed text-emerald-300"><code>{{ is_string($requestPayload) ? $requestPayload : json_encode($requestPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
                            </div>
                        @endif
                        <p class="font-mono text-[11px] text-slate-400">
                            Include a valid Bearer token with every request
                        </p>
                    </div>
                </div>

                @if ($codeSamples->isNotEmpty())
                    <div class="overflow-hidden rounded-2xl bg-[#0b1f3a] text-slate-100 shadow-lg shadow-slate-900/10" x-data="{ tab: '{{ $codeSamples->first()?->language?->value }}' }">
                        <div class="flex items-center justify-between gap-3 border-b border-white/10 px-4 py-3">
                            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-violet-300">Code sample</p>
                            <div class="flex flex-wrap gap-1">
                                @foreach ($codeSamples as $sample)
                                    <button
                                        type="button"
                                        class="rounded-md px-2 py-1 text-[10px] font-semibold uppercase tracking-wide transition"
                                        :class="tab === '{{ $sample->language?->value }}' ? 'bg-white/15 text-white' : 'text-slate-400 hover:text-white'"
                                        @click="tab = '{{ $sample->language?->value }}'"
                                    >
                                        {{ $sample->language?->label() ?? $sample->language?->value }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <div class="p-4">
                            @foreach ($codeSamples as $sample)
                                <div x-show="tab === '{{ $sample->language?->value }}'" x-cloak>
                                    <pre class="overflow-x-auto rounded-xl bg-[#071528] p-3 font-mono text-xs leading-relaxed text-emerald-300"><code>{{ $sample->code }}</code></pre>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Response cards --}}
            <div class="space-y-4">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div class="border-b border-emerald-100 bg-emerald-50 px-4 py-2.5 dark:border-emerald-900/40 dark:bg-emerald-950/40">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-300">
                            {{ $successExample?->title ?: 'Success response' }}
                        </p>
                    </div>
                    <div class="p-4">
                        @php
                            $successPayload = $successExample?->response
                                ?? $successResponse?->example
                                ?? ['status' => 'success', 'message' => 'OK'];
                        @endphp
                        <pre class="overflow-x-auto rounded-xl bg-slate-50 p-3 font-mono text-xs leading-relaxed text-slate-800 dark:bg-slate-900 dark:text-slate-100"><code>{{ is_string($successPayload) ? $successPayload : json_encode($successPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div class="border-b border-rose-100 bg-rose-50 px-4 py-2.5 dark:border-rose-900/40 dark:bg-rose-950/40">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-rose-700 dark:text-rose-300">
                            @if ($failureExample && str_contains(strtolower((string) $failureExample->title), 'duplicate'))
                                Failure response (duplicate)
                            @else
                                Failure response
                            @endif
                        </p>
                    </div>
                    <div class="p-4">
                        @php
                            $failurePayload = $failureExample?->response
                                ?? $failureResponse?->example
                                ?? [
                                    'status' => 'error',
                                    'message' => 'Request failed. Check credentials, wallet balance, or validation errors.',
                                ];
                        @endphp
                        <pre class="overflow-x-auto rounded-xl bg-slate-50 p-3 font-mono text-xs leading-relaxed text-slate-800 dark:bg-slate-900 dark:text-slate-100"><code>{{ is_string($failurePayload) ? $failurePayload : json_encode($failurePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
                    </div>
                </div>
            </div>
        </div>

        @if ($leftSections->isNotEmpty())
            <div class="mt-10 space-y-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-800 dark:bg-slate-950">
                @foreach ($leftSections as $section)
                    {!! $renderer->render($section) !!}
                @endforeach
            </div>
        @endif

        @if ($document->related->isNotEmpty())
            <div class="mt-8">
                <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Related APIs</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($document->related as $related)
                        <a
                            href="{{ $related->url }}"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:border-sky-200 hover:text-[#0b1f3a] dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200"
                        >
                            <span class="font-mono text-[10px] font-bold uppercase text-slate-400">{{ $related->method?->value }}</span>
                            {{ $related->label ?: $related->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
