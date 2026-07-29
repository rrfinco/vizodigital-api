<x-docs.section-frame :label="$label" :anchor="$anchor">
    <ul class="space-y-2 text-sm">
        @foreach ($headers as $header)
            <li class="rounded-2xl border border-portal-border px-4 py-3 dark:border-slate-800">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono font-medium">{{ $header->name }}</span>
                    <span class="text-slate-400">· {{ $header->type }}</span>
                    @if ($header->required)
                        <span class="text-xs font-semibold uppercase text-rose-600">required</span>
                    @endif
                </div>
                @if ($header->description)
                    <p class="mt-1 text-slate-500">{{ $header->description }}</p>
                @endif
                @if ($header->example)
                    <p class="mt-1 font-mono text-xs text-slate-400">Example: {{ $header->example }}</p>
                @endif
            </li>
        @endforeach
    </ul>
</x-docs.section-frame>
