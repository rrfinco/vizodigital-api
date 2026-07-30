@foreach ($nodes as $node)
    @php
        $hasActiveChild = false;
        if ($node->children->isNotEmpty()) {
            foreach ($node->children as $child) {
                if ($child->active) {
                    $hasActiveChild = true;
                    break;
                }
                if ($child->children->isNotEmpty() && $child->children->contains(fn ($c) => $c->active)) {
                    $hasActiveChild = true;
                    break;
                }
            }
        }
        $methodBadge = strtoupper((string) ($node->badge ?? ''));
        $isSectionHeader = ! $node->href && $depth === 0;
        $isOpenDefault = $node->active || $hasActiveChild || $isSectionHeader;
    @endphp

    <div @if (! $isSectionHeader) x-data="{ collapsed: {{ $isOpenDefault ? 'false' : 'true' }} }" @endif class="w-full">
        @if ($isSectionHeader)
            <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                {{ $node->label }}
            </p>
            @if ($node->children->isNotEmpty())
                <div class="space-y-0.5">
                    @include('docs.partials.nav-nodes', ['nodes' => $node->children, 'depth' => $depth + 1])
                </div>
            @endif
        @elseif ($node->href)
            <div class="flex w-full items-center justify-between rounded-lg group">
                <a
                    href="{{ $node->href }}"
                    @if ($node->external) target="_blank" rel="noopener noreferrer" @endif
                    @class([
                        'flex flex-1 min-w-0 items-center gap-2 rounded-lg px-3 py-2 text-sm transition',
                        'bg-sky-50 font-semibold text-sky-800 dark:bg-sky-950/50 dark:text-sky-200' => $node->active,
                        'text-slate-600 hover:bg-slate-50 hover:text-[#0b1f3a] dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white' => ! $node->active,
                    ])
                >
                    <span class="truncate">{{ $node->label }}</span>
                    @if ($node->badge && ! in_array(strtoupper($node->badge), ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'], true))
                        <span class="ml-auto text-[10px] uppercase tracking-wide text-slate-400">{{ $node->badge }}</span>
                    @endif
                </a>
                @if ($node->children->isNotEmpty())
                    <button
                        type="button"
                        class="p-2 text-slate-400 hover:text-slate-600 focus:outline-none dark:hover:text-slate-200"
                        @click.stop.prevent="collapsed = !collapsed"
                    >
                        <svg class="h-3.5 w-3.5 transform transition-transform" :class="collapsed ? '-rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                @endif
            </div>

            @if ($node->children->isNotEmpty())
                <div class="mb-1 ml-3 space-y-0.5 border-l border-slate-200 pl-1 dark:border-slate-800" x-show="!collapsed" style="display: none;">
                    @include('docs.partials.nav-nodes', ['nodes' => $node->children, 'depth' => $depth + 1])
                </div>
            @endif
        @else
            <div
                @class([
                    'flex items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-slate-800 dark:text-slate-100',
                    'cursor-pointer select-none hover:bg-slate-50 dark:hover:bg-slate-900' => $node->children->isNotEmpty(),
                ])
                @if ($node->children->isNotEmpty())
                    @click="collapsed = !collapsed"
                @endif
            >
                <span class="truncate">{{ $node->label }}</span>
                @if ($node->children->isNotEmpty())
                    <svg class="h-3.5 w-3.5 text-slate-400 transform transition-transform" :class="collapsed ? '-rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                @endif
            </div>

            @if ($node->children->isNotEmpty())
                <div class="mb-1 ml-3 space-y-0.5 border-l border-slate-200 pl-1 dark:border-slate-800" x-show="!collapsed" style="display: none;">
                    @include('docs.partials.nav-nodes', ['nodes' => $node->children, 'depth' => $depth + 1])
                </div>
            @endif
        @endif
    </div>
@endforeach
