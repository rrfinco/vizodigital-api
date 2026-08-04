@php
    $operators = $this->operators();
    $counts = $this->categoryCounts();
    $hasFilters = $this->hasActiveFilters();
    $activeCategory = trim($this->category);
    $canManage = $this->canManageCommissions();
    $filteredCount = $this->filteredCount();
    $totalCount = $this->totalCount();
@endphp

<x-filament-panels::page>
    <div class="inspay-ops-page space-y-3">
        {{-- Compact single-row toolbar --}}
        <div
            wire:key="inspay-filters-{{ $this->filterVersion }}"
            class="inspay-ops-toolbar"
        >
            @if ($canManage)
                <select
                    class="inspay-ops-toolbar-control inspay-ops-toolbar-control--user"
                    wire:model.live="selectedUserId"
                    title="Developer"
                >
                    @foreach ($this->developerUsersForSelect() as $user)
                        <option value="{{ $user['id'] }}">{{ $user['label'] }}</option>
                    @endforeach
                </select>
            @endif

            <select
                class="inspay-ops-toolbar-control inspay-ops-toolbar-control--category"
                wire:model.live="category"
                title="Category"
            >
                <option value="">All categories ({{ number_format($totalCount) }})</option>
                @foreach ($counts as $cat => $count)
                    <option value="{{ $cat }}" @selected($activeCategory === $cat)>
                        {{ $cat }} ({{ number_format($count) }})
                    </option>
                @endforeach
            </select>

            <input
                type="search"
                class="inspay-ops-toolbar-control inspay-ops-toolbar-control--search"
                wire:model.live.debounce.300ms="search"
                placeholder="Search name or code…"
                autocomplete="off"
            />

            <span class="inspay-ops-toolbar-count">
                <strong>{{ number_format($filteredCount) }}</strong>/{{ number_format($totalCount) }}
            </span>

            @if ($hasFilters)
                <button
                    type="button"
                    wire:click="clearFilters"
                    class="inspay-ops-toolbar-btn inspay-ops-toolbar-btn--ghost"
                    title="Clear filters"
                >
                    Clear
                </button>
            @endif

            @if ($canManage)
                <button
                    type="button"
                    wire:click="saveCommissions"
                    class="inspay-ops-toolbar-btn inspay-ops-toolbar-btn--primary"
                >
                    Save page
                </button>
            @endif
        </div>

        {{-- Results --}}
        <div class="inspay-ops-panel">
            <div class="inspay-ops-panel__head">
                <span class="inspay-ops-panel__title">
                    Operators · {{ number_format($operators->count()) }} shown
                </span>
                <span class="inspay-ops-panel__hint">
                    Use code as <code>opcode</code>
                    @if ($canManage)
                        · edit then Save page
                    @endif
                </span>
            </div>

            <div
                wire:loading.class="opacity-50 pointer-events-none"
                wire:target="category,search,selectCategory,clearFilters,clearCategory,clearSearch,gotoPage,nextPage,previousPage,selectedUserId,saveCommissions"
                class="transition-opacity"
            >
                @if ($operators->isEmpty())
                    <div class="wallet-empty">
                        <div class="wallet-empty__icon" aria-hidden="true">
                            <x-filament::icon icon="heroicon-o-magnifying-glass" class="h-7 w-7" />
                        </div>
                        <p class="wallet-empty__title">No operators found</p>
                        <p class="wallet-empty__hint">Try another category or search term.</p>
                        @if ($hasFilters)
                            <div class="mt-3">
                                <button type="button" wire:click="clearFilters" class="inspay-ops-toolbar-btn inspay-ops-toolbar-btn--primary">
                                    Clear filters
                                </button>
                            </div>
                        @endif
                    </div>
                @else
                    {{-- Always table (no card layout — Tailwind md: breakpoints unreliable in this view) --}}
                    <div class="inspay-ops-table-wrap">
                        <table class="inspay-ops-table">
                            <colgroup>
                                <col style="width: 14%" />
                                <col style="width: 32%" />
                                <col style="width: 10%" />
                                <col style="width: 12%" />
                                <col style="width: 12%" />
                                <col style="width: 12%" />
                                <col style="width: 8%" />
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Operator</th>
                                    <th>Code</th>
                                    <th>Type</th>
                                    <th>Comm.</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($operators as $op)
                                    <tr wire:key="op-{{ $op['code'] }}-{{ $loop->index }}">
                                        <td>
                                            <span class="inspay-ops-badge" title="{{ $op['category'] }}">
                                                {{ $op['category'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="inspay-ops-name" title="{{ $op['name'] }}">
                                                {{ $op['name'] }}
                                            </div>
                                        </td>
                                        <td>
                                            <code class="inspay-ops-code">{{ $op['code'] }}</code>
                                        </td>
                                        <td>
                                            @if ($canManage)
                                                <select
                                                    class="inspay-ops-compact-select"
                                                    wire:model="commissionRows.{{ $op['code'] }}.commission_type"
                                                >
                                                    <option value="percentage">%</option>
                                                    <option value="flat">Flat ₹</option>
                                                </select>
                                            @else
                                                <span class="inspay-ops-cell-text">
                                                    {{ $op['commission_type'] === 'flat' ? 'Flat ₹' : '%' }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($canManage)
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    class="inspay-ops-compact-input"
                                                    wire:model="commissionRows.{{ $op['code'] }}.commission_value"
                                                />
                                            @else
                                                <span class="inspay-ops-cell-text inspay-ops-cell-text--strong">
                                                    {{ $op['commission_value'] }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($canManage)
                                                <select
                                                    class="inspay-ops-compact-select"
                                                    wire:model="commissionRows.{{ $op['code'] }}.status"
                                                >
                                                    <option value="Active">On</option>
                                                    <option value="Inactive">Off</option>
                                                </select>
                                            @else
                                                <span class="inspay-ops-status {{ $op['status'] === 'Active' ? 'inspay-ops-status--on' : 'inspay-ops-status--off' }}">
                                                    {{ $op['status'] === 'Active' ? 'On' : 'Off' }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="inspay-ops-td-action">
                                            <button
                                                type="button"
                                                class="inspay-ops-copy"
                                                title="Copy {{ $op['code'] }}"
                                                x-data="{ copied: false }"
                                                x-on:click="
                                                    navigator.clipboard.writeText({{ Js::from($op['code']) }}).then(() => {
                                                        copied = true;
                                                        $wire.copyCode({{ Js::from($op['code']) }});
                                                        setTimeout(() => copied = false, 1500);
                                                    })
                                                "
                                            >
                                                <span x-show="!copied" class="inline-flex items-center">
                                                    <x-filament::icon icon="heroicon-m-clipboard-document" class="h-3 w-3" />
                                                </span>
                                                <span x-cloak x-show="copied" class="inline-flex items-center text-teal-700 dark:text-teal-300">
                                                    <x-filament::icon icon="heroicon-m-check" class="h-3 w-3" />
                                                </span>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="inspay-ops-pagination">
                        {{ $operators->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
