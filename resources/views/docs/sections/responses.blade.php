<x-docs.section-frame :label="$label" :anchor="$anchor">
    <ul class="docs-response-grid text-sm">
        @foreach ($responses as $response)
            @php
                $isSuccess = (int) $response->status_code >= 200 && (int) $response->status_code < 300;
                $isError = (int) $response->status_code >= 400;
            @endphp
            <li @class([
                'overflow-hidden rounded-xl border',
                'border-emerald-100 dark:border-emerald-900/40' => $isSuccess,
                'border-rose-100 dark:border-rose-900/40' => $isError,
                'border-slate-200 dark:border-slate-800' => ! $isSuccess && ! $isError,
            ])>
                <div @class([
                    'border-b px-4 py-2.5',
                    'border-emerald-100 bg-emerald-50 dark:border-emerald-900/40 dark:bg-emerald-950/40' => $isSuccess,
                    'border-rose-100 bg-rose-50 dark:border-rose-900/40 dark:bg-rose-950/40' => $isError,
                    'border-slate-100 bg-slate-50 dark:border-slate-800 dark:bg-slate-900' => ! $isSuccess && ! $isError,
                ])>
                    <div class="flex flex-wrap items-center gap-2">
                        <span @class([
                            'font-mono text-xs font-bold',
                            'text-emerald-700 dark:text-emerald-300' => $isSuccess,
                            'text-rose-700 dark:text-rose-300' => $isError,
                            'text-slate-700 dark:text-slate-200' => ! $isSuccess && ! $isError,
                        ])>{{ $response->status_code }}</span>
                        @if ($response->content_type)
                            <span class="text-xs text-slate-400">· {{ $response->content_type }}</span>
                        @endif
                        @if ($response->is_default)
                            <span class="text-[10px] font-semibold uppercase tracking-wide text-sky-600">default</span>
                        @endif
                    </div>
                    @if ($response->description)
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $response->description }}</p>
                    @endif
                </div>
                <div class="space-y-3 p-4">
                    @if ($response->schema)
                        <div>
                            <p class="mb-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Schema</p>
                            <x-docs.json-block :value="$response->schema" />
                        </div>
                    @endif
                    @if ($response->example)
                        <div>
                            <p class="mb-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Example</p>
                            <x-docs.json-block :value="$response->example" />
                        </div>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
</x-docs.section-frame>
