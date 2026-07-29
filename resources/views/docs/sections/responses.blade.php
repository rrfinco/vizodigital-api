<x-docs.section-frame :label="$label" :anchor="$anchor">
    <ul class="space-y-3 text-sm">
        @foreach ($responses as $response)
            <li class="rounded-2xl border border-portal-border px-4 py-3 dark:border-slate-800">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono font-semibold">{{ $response->status_code }}</span>
                    @if ($response->content_type)
                        <span class="text-slate-400">· {{ $response->content_type }}</span>
                    @endif
                    @if ($response->is_default)
                        <span class="text-xs font-semibold uppercase text-primary-600">default</span>
                    @endif
                </div>
                @if ($response->description)
                    <p class="mt-1 text-slate-500">{{ $response->description }}</p>
                @endif
                @if ($response->schema)
                    <div class="mt-3">
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Schema</p>
                        <x-docs.json-block :value="$response->schema" />
                    </div>
                @endif
                @if ($response->example)
                    <div class="mt-3">
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Example</p>
                        <x-docs.json-block :value="$response->example" />
                    </div>
                @endif
            </li>
        @endforeach
    </ul>
</x-docs.section-frame>
