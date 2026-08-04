<x-filament-panels::page>
    <x-slot name="heading">
        Developer Plan API access
    </x-slot>

    <x-slot name="description">
        Apne developers ke liye Plan API enable karein aur per-call fee set karein. Min = aapka white-label cost. Extra = aapka margin (float pe credit).
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
                        Save access
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        <div class="grid gap-4 grid-cols-1 md:grid-cols-2">
            @foreach ($this->services() as $service)
                @php
                    $key = $service['key'];
                    $row = $this->rows[$key] ?? [];
                    $userFee = (float) ($row['per_call_fee'] ?? 0);
                    $minFee = (float) ($row['min_fee'] ?? 0);
                    $margin = max(0, round($userFee - $minFee, 2));
                @endphp

                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 flex flex-col justify-between gap-4">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $service['label'] }}
                        </h4>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $service['description'] }}
                        </p>
                        <p class="mt-2 font-mono text-[10px] text-gray-400">
                            {{ $key }} · Min ₹{{ $row['min_fee'] ?? '0.00' }}
                        </p>
                    </div>

                    <div class="grid gap-3 grid-cols-2">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                User fee / call (₹)
                            </label>
                            <x-filament::input.wrapper>
                                <x-filament::input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    inputmode="decimal"
                                    wire:model.live="rows.{{ $key }}.per_call_fee"
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

                    <div class="rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-800 dark:bg-emerald-400/10 dark:text-emerald-300">
                        Your margin / call:
                        <span class="font-semibold">₹{{ number_format($margin, 2) }}</span>
                        <span class="opacity-80">(user fee − your cost)</span>
                    </div>
                </div>
            @endforeach
        </div>

        @if (empty($this->developerUsersForSelect()->all()))
            <div class="rounded-xl bg-amber-50 p-4 border border-amber-200 dark:bg-amber-950/20 dark:border-amber-900/50 text-sm text-amber-800 dark:text-amber-300">
                No developers under your white-label yet.
            </div>
        @endif
    </div>
</x-filament-panels::page>
