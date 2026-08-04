<x-filament-panels::page>
    <div class="space-y-4">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            One row per recharge order. Failed attempts stay marked as failed (wallet refunds are not listed separately).
        </p>

        @if ($this->transactions->isEmpty())
            <x-filament::section>
                <div class="wallet-empty">
                    <div class="wallet-empty__icon" aria-hidden="true">
                        <x-filament::icon icon="heroicon-o-receipt-percent" class="h-7 w-7" />
                    </div>
                    <p class="wallet-empty__title">No recharge transactions yet</p>
                    <p class="wallet-empty__hint">
                        When you call the recharge API, each order will appear here with its final status.
                    </p>
                </div>
            </x-filament::section>
        @else
            <x-filament::section>
                {{-- Mobile cards --}}
                <div class="space-y-3 md:hidden">
                    @foreach ($this->transactions as $txn)
                        <div wire:key="txn-m-{{ $txn->id }}" class="inspay-ops-card space-y-2">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $this->operatorName((int) $txn->operator_sp_key, (string) $txn->operator_type) }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $txn->account_number }} · ₹{{ number_format((float) $txn->amount, 2) }}
                                    </p>
                                    <p class="mt-1 text-[11px] text-gray-400">
                                        {{ $txn->created_at?->format('M d, Y · h:i A') }}
                                    </p>
                                    @if (filled($txn->client_request_id))
                                        <p class="mt-1 text-[11px] font-mono text-gray-500 break-all">
                                            Order: {{ $txn->client_request_id }}
                                        </p>
                                    @endif
                                    @if ($txn->status === 'failed' && filled($txn->error_message))
                                        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                            {{ $txn->error_message }}
                                        </p>
                                    @endif
                                </div>
                                <div class="text-right shrink-0 space-y-1">
                                    <x-filament::badge :color="$this->statusColor((string) $txn->status)">
                                        {{ ucfirst((string) $txn->status) }}
                                    </x-filament::badge>
                                    @if ($txn->status === 'success')
                                        <p class="text-[11px] text-emerald-600 dark:text-emerald-400">
                                            +₹{{ number_format((float) $txn->commission_amount, 2) }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Desktop table --}}
                <div class="wallet-table-wrap mt-1 hidden md:block">
                    <table class="wallet-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Operator / Number</th>
                                <th>Amount</th>
                                <th>Order ID</th>
                                <th>Provider</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->transactions as $txn)
                                <tr wire:key="txn-d-{{ $txn->id }}">
                                    <td>
                                        <div class="wallet-table__date">
                                            {{ $txn->created_at?->format('M d, Y') }}
                                        </div>
                                        <div class="wallet-table__time">
                                            {{ $txn->created_at?->format('h:i A') }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="wallet-table__date">
                                            {{ $this->operatorName((int) $txn->operator_sp_key, (string) $txn->operator_type) }}
                                        </div>
                                        <div class="wallet-table__time font-mono">
                                            {{ $txn->account_number }} · SP {{ $txn->operator_sp_key }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-mono text-sm font-semibold">
                                            ₹{{ number_format((float) $txn->amount, 2) }}
                                        </div>
                                        @if ($txn->status === 'success' && (float) $txn->commission_amount > 0)
                                            <div class="wallet-table__time text-emerald-600 dark:text-emerald-400">
                                                Comm ₹{{ number_format((float) $txn->commission_amount, 2) }}
                                                ({{ number_format((float) $txn->commission_percentage, 2) }}%)
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="wallet-table__time font-mono break-all max-w-[11rem]">
                                            {{ $txn->client_request_id ?: '—' }}
                                        </div>
                                        <div class="wallet-table__time font-mono break-all max-w-[11rem] opacity-70">
                                            {{ $txn->api_request_id }}
                                        </div>
                                    </td>
                                    <td>
                                        @if ($txn->status === 'success')
                                            <div class="wallet-table__time font-mono break-all max-w-[10rem]">
                                                {{ $txn->rpid ?: '—' }}
                                            </div>
                                            <div class="wallet-table__time font-mono break-all max-w-[10rem] opacity-70">
                                                {{ $txn->opid ?: '—' }}
                                            </div>
                                        @elseif ($txn->status === 'failed')
                                            <div class="text-xs text-rose-600 dark:text-rose-400 max-w-[12rem] break-words">
                                                {{ $txn->error_message ?: 'Failed' }}
                                            </div>
                                        @else
                                            <div class="wallet-table__time">Awaiting operator</div>
                                        @endif
                                    </td>
                                    <td>
                                        <x-filament::badge :color="$this->statusColor((string) $txn->status)">
                                            {{ ucfirst((string) $txn->status) }}
                                        </x-filament::badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $this->transactions->links() }}
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
