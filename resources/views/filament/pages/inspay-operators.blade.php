@php
    $operators = $this->operators();
    $counts = $this->categoryCounts();
    $hasFilters = $this->hasActiveFilters();
    $activeCategory = trim($this->category);
    $activeSearch = trim($this->search);
@endphp

<x-filament-panels::page>
    <div class="inspay-ops-page space-y-6">
        <p class="text-sm text-gray-600 dark:text-gray-300 -mt-2">
            Look up InsPay API operator codes for bill pay and related services. Filter by category or search by name / code.
        </p>

        {{-- Simple & Clean Filters --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-funnel" class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                    <span class="font-semibold text-sm">Filter operators</span>
                </div>
            </x-slot>

            <div wire:key="inspay-filters-{{ $this->filterVersion }}" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="min-w-0">
                        <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Category
                        </label>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="category">
                                <option value="">All categories ({{ number_format($this->totalCount()) }})</option>
                                @foreach ($counts as $cat => $count)
                                    <option value="{{ $cat }}" @selected($activeCategory === $cat)>
                                        {{ $cat }} ({{ number_format($count) }})
                                    </option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>

                    <div class="min-w-0">
                        <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Search
                        </label>
                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="search"
                                wire:model.live.debounce.300ms="search"
                                placeholder="Search operator name or code…"
                                autocomplete="off"
                            />
                        </x-filament::input.wrapper>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between pt-1">
                    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <span>
                            Showing <strong class="font-semibold text-gray-900 dark:text-white">{{ number_format($this->filteredCount()) }}</strong> of {{ number_format($this->totalCount()) }} operators
                        </span>

                        @if ($hasFilters)
                            <div class="flex flex-wrap items-center gap-1.5 ml-1">
                                @if ($activeCategory !== '')
                                    <button
                                        type="button"
                                        wire:click="clearCategory"
                                        class="inspay-ops-filter-chip"
                                        title="Remove category filter"
                                    >
                                        <span>{{ $activeCategory }}</span>
                                        <x-filament::icon icon="heroicon-m-x-mark" class="h-3.5 w-3.5 shrink-0" />
                                    </button>
                                @endif

                                @if ($activeSearch !== '')
                                    <button
                                        type="button"
                                        wire:click="clearSearch"
                                        class="inspay-ops-filter-chip"
                                        title="Remove search filter"
                                    >
                                        <span>“{{ $activeSearch }}”</span>
                                        <x-filament::icon icon="heroicon-m-x-mark" class="h-3.5 w-3.5 shrink-0" />
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if ($hasFilters)
                        <div>
                            <x-filament::button
                                wire:click="clearFilters"
                                color="gray"
                                size="sm"
                                icon="heroicon-m-x-mark"
                            >
                                Clear filters
                            </x-filament::button>
                        </div>
                    @endif
                </div>
            </div>
        </x-filament::section>

        {{-- Results --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <span class="wallet-icon-wrap">
                        <x-filament::icon icon="heroicon-o-queue-list" class="h-4 w-4 text-teal-600 dark:text-teal-300" />
                    </span>
                    <span class="font-semibold">Operator codes</span>
                </div>
            </x-slot>

            <x-slot name="description">
                Use the code as <code class="text-xs">opcode</code> in bill payment APIs
            </x-slot>

            <div
                wire:loading.class="opacity-50 pointer-events-none"
                wire:target="category,search,selectCategory,clearFilters,clearCategory,clearSearch,gotoPage,nextPage,previousPage"
                class="transition-opacity"
            >
                @if ($operators->isEmpty())
                    <div class="wallet-empty">
                        <div class="wallet-empty__icon" aria-hidden="true">
                            <x-filament::icon icon="heroicon-o-magnifying-glass" class="h-7 w-7" />
                        </div>
                        <p class="wallet-empty__title">No operators found</p>
                        <p class="wallet-empty__hint">
                            Try another category or search term.
                        </p>
                        @if ($hasFilters)
                            <div class="mt-3">
                                <x-filament::button wire:click="clearFilters" color="primary" size="sm">
                                    Clear filters
                                </x-filament::button>
                            </div>
                        @endif
                    </div>
                @else
                    {{-- Mobile cards --}}
                    <div class="inspay-ops-cards space-y-3 md:hidden">
                        @foreach ($operators as $op)
                            <div
                                wire:key="op-card-{{ $op['code'] }}-{{ $loop->index }}"
                                class="inspay-ops-card"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <span class="inspay-ops-badge">{{ $op['category'] }}</span>
                                        <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-white break-words">
                                            {{ $op['name'] }}
                                        </p>
                                        <code class="inspay-ops-code mt-2 inline-flex">{{ $op['code'] }}</code>
                                    </div>
                                    <button
                                        type="button"
                                        class="inspay-ops-copy shrink-0"
                                        x-data="{ copied: false }"
                                        x-on:click="
                                            navigator.clipboard.writeText({{ Js::from($op['code']) }}).then(() => {
                                                copied = true;
                                                $wire.copyCode({{ Js::from($op['code']) }});
                                                setTimeout(() => copied = false, 1500);
                                            })
                                        "
                                    >
                                        <span x-show="!copied" class="inline-flex items-center gap-1">
                                            <x-filament::icon icon="heroicon-m-clipboard-document" class="h-3.5 w-3.5" />
                                            Copy
                                        </span>
                                        <span x-cloak x-show="copied" class="inline-flex items-center gap-1 text-teal-700 dark:text-teal-300">
                                            <x-filament::icon icon="heroicon-m-check" class="h-3.5 w-3.5" />
                                            Copied
                                        </span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Desktop table --}}
                    <div class="wallet-table-wrap mt-1 hidden md:block">
                        <table class="wallet-table inspay-ops-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Service / Operator</th>
                                    <th>API code</th>
                                    <th class="w-24"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($operators as $op)
                                    <tr wire:key="op-{{ $op['code'] }}-{{ $loop->index }}">
                                        <td>
                                            <span class="inspay-ops-badge">
                                                {{ $op['category'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="wallet-table__date" title="{{ $op['name'] }}">
                                                {{ $op['name'] }}
                                            </div>
                                        </td>
                                        <td>
                                            <code class="inspay-ops-code">{{ $op['code'] }}</code>
                                        </td>
                                        <td>
                                            <button
                                                type="button"
                                                class="inspay-ops-copy"
                                                x-data="{ copied: false }"
                                                x-on:click="
                                                    navigator.clipboard.writeText({{ Js::from($op['code']) }}).then(() => {
                                                        copied = true;
                                                        $wire.copyCode({{ Js::from($op['code']) }});
                                                        setTimeout(() => copied = false, 1500);
                                                    })
                                                "
                                            >
                                                <span x-show="!copied" class="inline-flex items-center gap-1">
                                                    <x-filament::icon icon="heroicon-m-clipboard-document" class="h-3.5 w-3.5" />
                                                    Copy
                                                </span>
                                                <span x-cloak x-show="copied" class="inline-flex items-center gap-1 text-teal-700 dark:text-teal-300">
                                                    <x-filament::icon icon="heroicon-m-check" class="h-3.5 w-3.5" />
                                                    Copied
                                                </span>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        {{ $operators->links() }}
                    </div>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
