<x-filament-panels::page>
    <x-slot name="heading">
        My recharge rates
    </x-slot>

    <x-slot name="description">
        Admin ne aapke white-label ke liye jo recharge commission set kiya hai — read only. Developers ko isse zyada nahi de sakte.
    </x-slot>

    <div class="space-y-6">
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

                        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 flex flex-col justify-between gap-4">
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
                                        <span class="font-mono text-[10px] opacity-80">SP: {{ $op['sp_key'] }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-3 grid-cols-2">
                                <div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Your rate</div>
                                    <div class="mt-0.5 text-lg font-semibold text-gray-950 dark:text-white">
                                        {{ $row['commission_percentage'] ?? '0.00' }}%
                                    </div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Status</div>
                                    <div class="mt-1">
                                        @if (($row['status'] ?? 'Active') === 'Active')
                                            <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-400/10 dark:text-emerald-400">Active</span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-400/10 dark:text-rose-400">Inactive</span>
                                        @endif
                                    </div>
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

                        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 flex flex-col justify-between gap-4">
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
                                        <span class="font-mono text-[10px] opacity-80">SP: {{ $op['sp_key'] }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-3 grid-cols-2">
                                <div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Your rate</div>
                                    <div class="mt-0.5 text-lg font-semibold text-gray-950 dark:text-white">
                                        {{ $row['commission_percentage'] ?? '0.00' }}%
                                    </div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Status</div>
                                    <div class="mt-1">
                                        @if (($row['status'] ?? 'Active') === 'Active')
                                            <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-400/10 dark:text-emerald-400">Active</span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-400/10 dark:text-rose-400">Inactive</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
