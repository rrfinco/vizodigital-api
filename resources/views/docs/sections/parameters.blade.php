<x-docs.section-frame :label="$label" :anchor="$anchor">
    <ul class="space-y-2 text-sm">
        @foreach ($parameters as $parameter)
            <li class="rounded-2xl border border-portal-border px-4 py-3 dark:border-slate-800">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono font-medium">{{ $parameter->name }}</span>
                    <span class="text-slate-400">· {{ $parameter->location?->value }} · {{ $parameter->type }}</span>
                    @if ($parameter->required)
                        <span class="text-xs font-semibold uppercase text-rose-600">required</span>
                    @endif
                </div>
                @if ($parameter->description)
                    <p class="mt-1 text-slate-500">{{ $parameter->description }}</p>
                @endif
                @if ($parameter->example)
                    <p class="mt-1 font-mono text-xs text-slate-400">Example: {{ is_array($parameter->example) ? json_encode($parameter->example) : $parameter->example }}</p>
                @endif
                @if ($parameter->schema)
                    <div class="mt-3">
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Schema</p>
                        <x-docs.json-block :value="$parameter->schema" />
                    </div>
                @endif
            </li>
        @endforeach
    </ul>
</x-docs.section-frame>
