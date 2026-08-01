<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full flex-wrap gap-2">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-arrow-path" class="h-4 w-4 text-teal-600 dark:text-teal-400" />
                    <span class="font-bold text-gray-900 dark:text-white text-sm">Transaction History</span>
                </div>
                <a
                    href="{{ \App\Filament\User\Pages\Wallet::getUrl() }}"
                    class="text-xs font-semibold text-teal-600 dark:text-teal-400 hover:underline flex items-center gap-1"
                >
                    View Wallet &rarr;
                </a>
            </div>
        </x-slot>

        @php
            $txs = $this->getTransactions();
        @endphp

        @if ($txs->isEmpty())
            <div class="py-6 text-center">
                <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400">
                    <x-filament::icon icon="heroicon-o-receipt-percent" class="h-5 w-5" />
                </div>
                <p class="text-xs font-semibold text-gray-900 dark:text-white">No transactions yet</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Your deposits and API usage will appear here.</p>
                <div class="mt-3">
                    <a
                        href="{{ \App\Filament\User\Pages\Wallet::getUrl() }}"
                        class="inline-flex items-center gap-1 rounded-md bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-teal-500 transition"
                    >
                        Add Funds
                    </a>
                </div>
            </div>
        @else
            <div class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($txs as $tx)
                    <div class="flex items-center justify-between gap-3 py-2.5 px-1 first:pt-0 last:pb-0">
                        <div class="flex items-center gap-3 min-w-0">
                            <span @class([
                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold',
                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300' => $tx['is_credit'],
                                'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' => ! $tx['is_credit'],
                            ])>
                                {{ $tx['is_credit'] ? '+' : '-' }}
                            </span>
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-white text-xs truncate">
                                    {{ $tx['title'] }}
                                </p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">
                                    {{ $tx['description'] }}
                                </p>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500">
                                    {{ $tx['date'] }}
                                </p>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <p @class([
                                'font-mono text-xs font-bold',
                                'text-emerald-600 dark:text-emerald-400' => $tx['is_credit'],
                                'text-gray-900 dark:text-white' => ! $tx['is_credit'],
                            ])>
                                {{ $tx['is_credit'] ? '+' : '-' }}₹{{ number_format($tx['amount'], 2) }}
                            </p>
                            <span @class([
                                'inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-medium ring-1 ring-inset mt-0.5',
                                'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/30' => $tx['status_color'] === 'success',
                                'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/30' => $tx['status_color'] === 'warning',
                                'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/30' => $tx['status_color'] === 'danger',
                                'bg-gray-100 text-gray-700 ring-gray-500/10 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700' => $tx['status_color'] === 'gray',
                            ])>
                                {{ $tx['status'] }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>idget>
