<x-docs.section-frame :label="$label" :anchor="$anchor">
    @if ($environment)
        <p class="mb-3 text-xs text-slate-500">Showing examples for {{ $environment->name }}.</p>
    @endif
    <div class="space-y-4">
        @foreach ($examples as $example)
            <div class="rounded-2xl border border-portal-border px-4 py-3 dark:border-slate-800">
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <span class="font-medium text-portal-dark dark:text-white">{{ $example->title }}</span>
                    @if ($example->response_status)
                        <span class="font-mono text-xs text-slate-400">{{ $example->response_status }}</span>
                    @endif
                </div>
                @if ($example->description)
                    <p class="mt-1 text-sm text-slate-500">{{ $example->description }}</p>
                @endif
                @if ($example->request)
                    <div class="mt-3">
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Request</p>
                        <x-docs.json-block :value="$example->request" />
                    </div>
                @endif
                @if ($example->response)
                    <div class="mt-3">
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Response</p>
                        <x-docs.json-block :value="$example->response" />
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</x-docs.section-frame>
