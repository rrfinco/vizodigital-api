<x-filament-panels::page>
    <x-slot name="heading">
        Developer recharge commissions
    </x-slot>

    <x-slot name="description">
        Apne developers ke liye recharge commission set karein. Max = aapka white-label rate (My recharge rates).
    </x-slot>

    <div class="space-y-6">
        <x-filament::section>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end justify-between">
                <div class="flex-1 max-w-md">
                    <label class="block text-sm font-medium text-gray-950 dark:text-white mb-2">
                        Select developer
                    </label>

                    <x-filament::input.wrapper>
                        <x-filament::input.select
                            wire:model.live="selectedUserId"
                        >
                            @foreach ($this->developerUsersForSelect() as $user)
                                <option value="{{ $user['id'] }}">
                                    {{ $user['label'] }}
                                </option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <x-filament::button wire:click="save" color="primary" class="w-full sm:w-auto" icon="heroicon-m-check">
                        Save commissions
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        @php
            $operators = $this->operators();
            $mobileOperators = collect($operators)->where('type', 'mobile')->values()->all();
            $dthOperators = collect($operators)->where('type', 'dth')->values()->all();
        @endphp

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="space-y-3">
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white px-1">
                    Mobile Recharge Operators
                </h3>

                <div class="grid gap-4 grid-cols-1 md:grid-cols-2">
                    @foreach ($mobileOperators as $op)
                        @php
                            $key = $op['type'].'_'.$op['sp_key'];
                            $row = $this->rows[$key] ?? [];
                        @endphp

                        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 flex flex-col justify-between gap-4 transition duration-75 hover:ring-gray-950/10 dark:hover:ring-white/20">
                            <div class="flex items-center gap-3">
                                <div class="relative shrink-0">
                                    <img
                                        src="{{ $this->operatorLogoSrc($op) }}"
                                        onerror="this.onerror=null;this.src='{{ asset($op['logo_path']) }}';"
                                        alt="{{ $op['operator_name'] }}"
                                        class="h-10 w-10 rounded-lg border border-gray-200 bg-gray-50 p-1 dark:border-white/10 dark:bg-gray-800 object-contain"
                                        loading="lazy"
                                    />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <h4 class="truncate text-sm font-semibold text-gray-900 dark:text-white" title="{{ $op['operator_name'] }}">
                                        {{ $op['operator_name'] }}
                                    </h4>
                                    <div class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="inline-flex items-center rounded-md bg-blue-50 px-1.5 py-0.5 text-[10px] font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/30">
                                            Mobile
                                        </span>
                                        <span class="text-[10px] opacity-80">Max {{ $row['max_commission'] ?? '0.00' }}%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-3 grid-cols-2">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                        Commission (%)
                                    </label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="{{ $row['max_commission'] ?? 100 }}"
                                            inputmode="decimal"
                                            wire:model="rows.{{ $key }}.commission_percentage"
                                        />
                                    </x-filament::input.wrapper>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                        Status
                                    </label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input.select
                                            wire:model="rows.{{ $key }}.status"
                                        >
                                            <option value="Active">Active</option>
                                            <option value="Inactive">Inactive</option>
                                        </x-filament::input.select>
                                    </x-filament::input.wrapper>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="space-y-3">
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white px-1">
                    DTH Recharge Operators
                </h3>

                <div class="grid gap-4 grid-cols-1 md:grid-cols-2">
                    @foreach ($dthOperators as $op)
                        @php
                            $key = $op['type'].'_'.$op['sp_key'];
                            $row = $this->rows[$key] ?? [];
                        @endphp

                        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 flex flex-col justify-between gap-4 transition duration-75 hover:ring-gray-950/10 dark:hover:ring-white/20">
                            <div class="flex items-center gap-3">
                                <div class="relative shrink-0">
                                    <img
                                        src="{{ $this->operatorLogoSrc($op) }}"
                                        onerror="this.onerror=null;this.src='{{ asset($op['logo_path']) }}';"
                                        alt="{{ $op['operator_name'] }}"
                                        class="h-10 w-10 rounded-lg border border-gray-200 bg-gray-50 p-1 dark:border-white/10 dark:bg-gray-800 object-contain"
                                        loading="lazy"
                                    />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <h4 class="truncate text-sm font-semibold text-gray-900 dark:text-white" title="{{ $op['operator_name'] }}">
                                        {{ $op['operator_name'] }}
                                    </h4>
                                    <div class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="inline-flex items-center rounded-md bg-purple-50 px-1.5 py-0.5 text-[10px] font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10 dark:bg-purple-400/10 dark:text-purple-400 dark:ring-purple-400/30">
                                            DTH
                                        </span>
                                        <span class="text-[10px] opacity-80">Max {{ $row['max_commission'] ?? '0.00' }}%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-3 grid-cols-2">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                        Commission (%)
                                    </label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="{{ $row['max_commission'] ?? 100 }}"
                                            inputmode="decimal"
                                            wire:model="rows.{{ $key }}.commission_percentage"
                                        />
                                    </x-filament::input.wrapper>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                        Status
                                    </label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input.select
                                            wire:model="rows.{{ $key }}.status"
                                        >
                                            <option value="Active">Active</option>
                                            <option value="Inactive">Inactive</option>
                                        </x-filament::input.select>
                                    </x-filament::input.wrapper>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @if ($this->developerUsersForSelect()->isEmpty())
            <div class="rounded-xl bg-amber-50 p-4 border border-amber-200 dark:bg-amber-950/20 dark:border-amber-900/50 text-sm text-amber-800 dark:text-amber-300">
                No developers under your white-label yet. Approve KYC first.
            </div>
        @endif
    </div>
</x-filament-panels::page>
