<x-docs.section-frame :label="$label" :anchor="$anchor">
    <ul class="space-y-2 text-sm">
        @foreach ($errors as $error)
            <li class="rounded-2xl border border-portal-border px-4 py-3 dark:border-slate-800">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono font-semibold">{{ $error->error_code }}</span>
                    @if ($error->status_code)
                        <span class="text-slate-400">· HTTP {{ $error->status_code }}</span>
                    @endif
                </div>
                @if ($error->message)
                    <p class="mt-1 font-medium text-slate-700 dark:text-slate-200">{{ $error->message }}</p>
                @endif
                @if ($error->description)
                    <p class="mt-1 text-slate-500">{{ $error->description }}</p>
                @endif
                @if ($error->example)
                    <div class="mt-3">
                        <x-docs.json-block :value="$error->example" />
                    </div>
                @endif
            </li>
        @endforeach
    </ul>
</x-docs.section-frame>
