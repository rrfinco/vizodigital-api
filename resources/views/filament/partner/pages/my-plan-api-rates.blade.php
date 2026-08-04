<x-filament-panels::page>
    <x-slot name="heading">
        My Plan API rates
    </x-slot>

    <x-slot name="description">
        Admin ne aapke white-label ke liye jo Plan API cost set kiya hai — read only. Developers se isse kam fee nahi le sakte. Difference aapka margin hai.
    </x-slot>

    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Plan API margin earned</x-slot>
            <p class="text-3xl font-semibold tracking-tight text-emerald-600 dark:text-emerald-400">
                ₹{{ number_format($this->planApiMarginEarned(), 2) }}
            </p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Total credited to float as Plan API margin (user fee − your cost). Details also appear in wallet transactions.
            </p>
        </x-filament::section>

        <div class="grid gap-4 grid-cols-1 md:grid-cols-2">
            @foreach ($this->services() as $service)
                @php
                    $key = $service['key'];
                    $row = $this->rows[$key] ?? [];
                    $active = ($row['status'] ?? 'Inactive') === 'Active';
                @endphp

                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 flex flex-col justify-between gap-4">
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $service['label'] }}
                            </h4>
                            <span @class([
                                'inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-medium ring-1 ring-inset',
                                'bg-emerald-50 text-emerald-700 ring-emerald-700/10 dark:bg-emerald-400/10 dark:text-emerald-400 dark:ring-emerald-400/30' => $active,
                                'bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10' => ! $active,
                            ])>
                                {{ $row['status'] ?? 'Inactive' }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $service['description'] }}
                        </p>
                        <p class="mt-2 font-mono text-[10px] text-gray-400">
                            {{ $key }}
                        </p>
                    </div>

                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Your cost / call</div>
                        <div class="mt-0.5 text-lg font-semibold text-gray-950 dark:text-white">
                            ₹{{ $row['per_call_fee'] ?? '0.00' }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
