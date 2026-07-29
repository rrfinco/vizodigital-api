<x-docs.section-frame :label="$label" :anchor="$anchor">
    @if ($environment)
        <p class="mb-3 text-xs text-slate-500">Code samples for {{ $environment->name }}.</p>
    @endif
    <div class="space-y-4" x-data="{ tab: '{{ $samples->first()?->language?->value }}' }">
        <div class="flex flex-wrap gap-2">
            @foreach ($samples as $sample)
                <button
                    type="button"
                    class="rounded-2xl border px-3 py-1.5 text-xs font-medium transition"
                    :class="tab === '{{ $sample->language?->value }}'
                        ? 'border-primary-600 bg-primary-50 text-primary-700 dark:bg-primary-950/40 dark:text-primary-300'
                        : 'border-portal-border text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-900'"
                    @click="tab = '{{ $sample->language?->value }}'"
                >
                    {{ $sample->language?->label() ?? $sample->language?->value }}
                </button>
            @endforeach
        </div>
        @foreach ($samples as $sample)
            <div x-show="tab === '{{ $sample->language?->value }}'" x-cloak>
                <pre class="overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs leading-relaxed text-slate-100 dark:bg-black"><code>{{ $sample->code }}</code></pre>
            </div>
        @endforeach
    </div>
</x-docs.section-frame>
