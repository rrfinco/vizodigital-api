@props([
    'versionSlug' => null,
    'searchUrl' => null,
])

@php
    $searchUrl ??= route('docs.search');
    $minChars = (int) config('portal.search.min_chars', 2);
    $debounce = (int) config('portal.search.debounce_ms', 250);
@endphp

<div
    class="relative"
    x-data="docsSearch({
        url: @js($searchUrl),
        version: @js($versionSlug),
        minChars: {{ $minChars }},
        debounceMs: {{ $debounce }},
    })"
    @keydown.escape.window="close()"
    @click.outside="close()"
>
    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
    </svg>
    <input
        type="search"
        class="portal-input pl-10"
        placeholder="Search API…"
        autocomplete="off"
        x-model="query"
        @input.debounce.{{ $debounce }}ms="search()"
        @focus="open = results.length > 0 || query.length >= minChars"
        @keydown.arrow-down.prevent="move(1)"
        @keydown.arrow-up.prevent="move(-1)"
        @keydown.enter.prevent="go()"
    >
    <div
        x-show="open"
        x-cloak
        class="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-2xl border border-portal-border bg-white shadow-card dark:border-slate-700 dark:bg-slate-950"
    >
        <template x-if="loading">
            <p class="px-4 py-3 text-sm text-slate-500">Searching…</p>
        </template>
        <template x-if="!loading && query.length >= minChars && results.length === 0">
            <p class="px-4 py-3 text-sm text-slate-500">No results for “<span x-text="query"></span>”</p>
        </template>
        <ul x-show="!loading && results.length > 0" class="max-h-80 overflow-y-auto py-1">
            <template x-for="(result, index) in results" :key="result.url + index">
                <li>
                    <a
                        :href="result.url"
                        class="block px-4 py-2.5 transition"
                        :class="index === activeIndex
                            ? 'bg-primary-50 dark:bg-primary-950/40'
                            : 'hover:bg-slate-50 dark:hover:bg-slate-900'"
                        @mouseenter="activeIndex = index"
                    >
                        <div class="flex items-center gap-2">
                            <span class="rounded-lg bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400" x-text="result.type_label"></span>
                            <span class="truncate text-sm font-medium text-portal-dark dark:text-white" x-text="result.title"></span>
                        </div>
                        <p class="mt-0.5 truncate text-xs text-slate-500" x-show="result.snippet" x-text="result.snippet"></p>
                    </a>
                </li>
            </template>
        </ul>
    </div>
</div>
