<x-docs.section-frame :label="$label" :anchor="$anchor">
    <div class="space-y-4">
        @foreach ($bodies as $body)
            <div class="rounded-2xl border border-portal-border px-4 py-3 dark:border-slate-800">
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <span class="font-mono font-medium">{{ $body->content_type }}</span>
                    @if ($body->required)
                        <span class="text-xs font-semibold uppercase text-rose-600">required</span>
                    @endif
                </div>
                @if ($body->description)
                    <p class="mt-2 text-sm text-slate-500">{{ $body->description }}</p>
                @endif
                @if ($body->schema)
                    <div class="mt-3">
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Schema</p>
                        <x-docs.json-block :value="$body->schema" />
                    </div>
                @endif
                @if ($body->example)
                    <div class="mt-3">
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Example</p>
                        <x-docs.json-block :value="$body->example" />
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</x-docs.section-frame>
