@php
    $hasFilters = $this->hasActiveFilters();
    $mobile = $this->mobileOperators();
    $dth = $this->dthOperators();
    $all = $this->operators();
    $showMobile = $this->typeFilter === '' || $this->typeFilter === 'mobile';
    $showDth = $this->typeFilter === '' || $this->typeFilter === 'dth';
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <p class="text-sm text-gray-600 dark:text-gray-300 -mt-2">
            Use these <code class="rounded bg-gray-100 dark:bg-gray-800 px-1 py-0.5 text-xs font-mono">operator_sp_key</code> values with
            <code class="rounded bg-gray-100 dark:bg-gray-800 px-1 py-0.5 text-xs font-mono">operator_type</code> (<code class="rounded bg-gray-100 dark:bg-gray-800 px-1 py-0.5 text-xs font-mono">mobile</code> or <code class="rounded bg-gray-100 dark:bg-gray-800 px-1 py-0.5 text-xs font-mono">dth</code>)
            in the Recharge API.
        </p>

        {{-- Simple & Clean Filters --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-funnel" class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                    <span class="font-semibold text-sm">Filter operators</span>
                </div>
            </x-slot>

            <div wire:key="recharge-filters-{{ $this->filterVersion }}" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="min-w-0">
                        <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Type
                        </label>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="typeFilter">
                                <option value="">All (mobile + DTH)</option>
                                <option value="mobile">Mobile</option>
                                <option value="dth">DTH</option>
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
                                placeholder="Search by operator name or SP key…"
                                autocomplete="off"
                            />
                        </x-filament::input.wrapper>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between pt-1">
                    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <span>
                            Showing <strong class="font-semibold text-gray-900 dark:text-white">{{ $all->count() }}</strong> operators
                        </span>

                        @if ($hasFilters)
                            <div class="flex flex-wrap items-center gap-1.5 ml-1">
                                @if (trim($this->typeFilter) !== '')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-medium text-teal-700 dark:bg-teal-950/50 dark:text-teal-300 border border-teal-200 dark:border-teal-800">
                                        Type: {{ ucfirst($this->typeFilter) }}
                                    </span>
                                @endif

                                @if (trim($this->search) !== '')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-medium text-teal-700 dark:bg-teal-950/50 dark:text-teal-300 border border-teal-200 dark:border-teal-800">
                                        Search: “{{ trim($this->search) }}”
                                    </span>
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

        @if ($all->isEmpty())
            <x-filament::section>
                <div class="wallet-empty py-8 text-center">
                    <div class="wallet-empty__icon mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400">
                        <x-filament::icon icon="heroicon-o-magnifying-glass" class="h-6 w-6" />
                    </div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">No operators found</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Try another type or search term.</p>
                    @if ($hasFilters)
                        <div class="mt-4">
                            <x-filament::button wire:click="clearFilters" color="primary" size="sm">
                                Clear filters
                            </x-filament::button>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @else
            <div class="space-y-6">
                @if ($showMobile)
                    <div class="space-y-3">
                        <div class="flex items-center justify-between px-1">
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-device-phone-mobile" class="h-4 w-4 text-teal-600 dark:text-teal-400" />
                                Mobile Recharge Operators
                            </h3>
                            <span class="rounded-full bg-gray-100 dark:bg-gray-800 px-2.5 py-0.5 text-xs font-semibold text-gray-600 dark:text-gray-400">
                                {{ $mobile->count() }}
                            </span>
                        </div>

                        @if ($mobile->isEmpty())
                            <p class="text-sm text-gray-500 dark:text-gray-400 py-2 px-1">No mobile operators match your filters.</p>
                        @else
                            <div class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                                @foreach ($mobile as $op)
                                    @include('filament.pages.partials.recharge-operator-row', ['op' => $op])
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                @if ($showDth)
                    <div class="space-y-3">
                        <div class="flex items-center justify-between px-1">
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-tv" class="h-4 w-4 text-teal-600 dark:text-teal-400" />
                                DTH Operators
                            </h3>
                            <span class="rounded-full bg-gray-100 dark:bg-gray-800 px-2.5 py-0.5 text-xs font-semibold text-gray-600 dark:text-gray-400">
                                {{ $dth->count() }}
                            </span>
                        </div>

                        @if ($dth->isEmpty())
                            <p class="text-sm text-gray-500 dark:text-gray-400 py-2 px-1">No DTH operators match your filters.</p>
                        @else
                            <div class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                                @foreach ($dth as $op)
                                    @include('filament.pages.partials.recharge-operator-row', ['op' => $op])
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
