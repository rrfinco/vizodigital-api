@foreach ($nodes as $node)
    @if ($node->href)
        <a
            href="{{ $node->href }}"
            @if ($node->external) target="_blank" rel="noopener noreferrer" @endif
            @class([
                'flex items-center gap-2.5 rounded-2xl px-3 py-2 text-sm transition',
                'bg-primary-50 font-medium text-primary-700 dark:bg-primary-950/40 dark:text-primary-300' => $node->active,
                'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-900' => ! $node->active,
                'ml-3' => $depth > 0,
                'ml-6' => $depth > 1,
            ])
        >
            <span class="truncate">{{ $node->label }}</span>
            @if ($node->badge)
                <span class="ml-auto font-mono text-[10px] uppercase text-slate-400">{{ $node->badge }}</span>
            @endif
        </a>
    @else
        <div @class([
            'flex items-center gap-2.5 rounded-2xl px-3 py-2 text-sm text-slate-500',
            'ml-3' => $depth > 0,
        ])>
            <span class="truncate font-medium">{{ $node->label }}</span>
            @if ($node->badge)
                <span class="ml-auto text-[10px] uppercase tracking-wide text-slate-400">{{ $node->badge }}</span>
            @endif
        </div>
    @endif

    @if ($node->children->isNotEmpty())
        <div class="space-y-0.5">
            @include('docs.partials.nav-nodes', ['nodes' => $node->children, 'depth' => $depth + 1])
        </div>
    @endif
@endforeach
