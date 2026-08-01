@php
    $user = auth()->user();
    $recentTransactions = $this->getRecentTransactions();
    $presets = [500, 1000, 2000, 5000, 10000];
    $onlineEnabled = $this->isOnlineEnabled();
    $bankEnabled = $this->isBankTransferEnabled();
    $bankDetails = $this->getBankDetails();
    $anyMethodEnabled = $onlineEnabled || $bankEnabled;
@endphp

<x-filament-panels::page>
    <div class="wallet-page space-y-6">
        <p class="text-sm text-gray-600 dark:text-gray-300 -mt-2">
            Fund your main wallet for recharge APIs, and track commission earnings in one place.
        </p>

        {{-- Balance cards --}}
        <div class="wallet-balance-grid">
            <article class="wallet-balance-card wallet-balance-card--main">
                <div class="wallet-balance-card__top">
                    <span class="wallet-balance-card__label">
                        <x-filament::icon icon="heroicon-o-wallet" class="h-4 w-4" />
                        Main Wallet
                    </span>
                    <span class="wallet-balance-card__chip">Available</span>
                </div>
                <div class="wallet-balance-card__body">
                    <p class="wallet-balance-card__amount">
                        ₹{{ number_format($user->wallet_balance, 2) }}
                    </p>
                    <p class="wallet-balance-card__hint">
                        Used for mobile &amp; DTH recharge API calls
                    </p>
                </div>
            </article>

            <article class="wallet-balance-card wallet-balance-card--earn">
                <div class="wallet-balance-card__top">
                    <span class="wallet-balance-card__label">
                        <x-filament::icon icon="heroicon-o-presentation-chart-line" class="h-4 w-4" />
                        Earning Wallet
                    </span>
                    <span class="wallet-balance-card__chip wallet-balance-card__chip--light">Commission</span>
                </div>
                <div class="wallet-balance-card__body">
                    <p class="wallet-balance-card__amount">
                        ₹{{ number_format($user->earning_balance, 2) }}
                    </p>
                    <p class="wallet-balance-card__hint">
                        Lifetime commission from successful recharges
                    </p>
                </div>
            </article>
        </div>

        <div class="grid gap-6 lg:grid-cols-5">
            {{-- Add funds --}}
            <div class="lg:col-span-2">
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <span class="wallet-icon-wrap">
                                <x-filament::icon icon="heroicon-o-plus-circle" class="h-4 w-4 text-teal-600 dark:text-teal-300" />
                            </span>
                            <span class="font-semibold">Add Funds</span>
                        </div>
                    </x-slot>

                    <x-slot name="description">
                        @if ($anyMethodEnabled)
                            Choose a preset or enter a custom amount, then complete payment.
                        @else
                            Add funds is temporarily unavailable. Please contact support.
                        @endif
                    </x-slot>

                    @if (! $anyMethodEnabled)
                        <div class="wallet-empty mt-2">
                            <p class="wallet-empty__title">No payment methods enabled</p>
                            <p class="wallet-empty__hint">An admin must enable online payment or bank transfer.</p>
                        </div>
                    @else
                        <div class="mt-1 space-y-6">
                            @if ($onlineEnabled && $bankEnabled)
                                <div class="space-y-2.5">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Payment method
                                    </p>
                                    <div class="wallet-presets">
                                        <button
                                            type="button"
                                            wire:click="$set('paymentMethod', 'online')"
                                            @class([
                                                'wallet-preset',
                                                'wallet-preset--active' => $paymentMethod === 'online',
                                            ])
                                        >
                                            Online
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="$set('paymentMethod', 'bank_transfer')"
                                            @class([
                                                'wallet-preset',
                                                'wallet-preset--active' => $paymentMethod === 'bank_transfer',
                                            ])
                                        >
                                            Bank transfer
                                        </button>
                                    </div>
                                </div>
                            @endif

                            <div class="space-y-2.5">
                                <label for="amount" class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Amount (₹)
                                </label>

                                <x-filament::input.wrapper>
                                    <x-slot name="prefix">
                                        <span class="font-medium text-gray-500 dark:text-gray-400">₹</span>
                                    </x-slot>

                                    <x-filament::input
                                        type="number"
                                        wire:model.live="amount"
                                        id="amount"
                                        min="1"
                                        step="1"
                                        placeholder="0.00"
                                        required
                                    />
                                </x-filament::input.wrapper>

                                @error('amount')
                                    <p class="text-xs font-medium text-danger-600 dark:text-danger-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-3">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Quick amounts
                                </p>
                                <div class="wallet-presets">
                                    @foreach ($presets as $preset)
                                        <button
                                            type="button"
                                            wire:click="$set('amount', {{ $preset }})"
                                            @class([
                                                'wallet-preset',
                                                'wallet-preset--active' => (float) $amount === (float) $preset,
                                            ])
                                        >
                                            ₹{{ number_format($preset) }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            @if ($paymentMethod === 'online' && $onlineEnabled)
                                <form wire:submit.prevent="submitPayment" class="space-y-2.5 pt-1">
                                    <x-filament::button
                                        type="submit"
                                        color="primary"
                                        size="sm"
                                        icon="heroicon-m-arrow-right-circle"
                                    >
                                        <span wire:loading.remove wire:target="submitPayment">Proceed to Payment</span>
                                        <span wire:loading wire:target="submitPayment">Redirecting…</span>
                                    </x-filament::button>

                                    <p class="text-[11px] leading-relaxed text-gray-500 dark:text-gray-400">
                                        Secure checkout via payment gateway. Min ₹1 · Max ₹5,00,000
                                    </p>
                                </form>
                            @elseif ($paymentMethod === 'bank_transfer' && $bankEnabled)
                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-800/60 space-y-2">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Transfer to
                                    </p>
                                    @if ($bankDetails['account_name'])
                                        <div><span class="text-gray-500">Name:</span> <strong>{{ $bankDetails['account_name'] }}</strong></div>
                                    @endif
                                    @if ($bankDetails['account_number'])
                                        <div><span class="text-gray-500">Account:</span> <strong class="font-mono">{{ $bankDetails['account_number'] }}</strong></div>
                                    @endif
                                    @if ($bankDetails['ifsc'])
                                        <div><span class="text-gray-500">IFSC:</span> <strong class="font-mono">{{ $bankDetails['ifsc'] }}</strong></div>
                                    @endif
                                    @if ($bankDetails['bank_name'])
                                        <div><span class="text-gray-500">Bank:</span> <strong>{{ $bankDetails['bank_name'] }}</strong></div>
                                    @endif
                                    @if ($bankDetails['upi_id'])
                                        <div><span class="text-gray-500">UPI:</span> <strong class="font-mono">{{ $bankDetails['upi_id'] }}</strong></div>
                                    @endif
                                </div>

                                <form wire:submit.prevent="submitBankTransfer" class="space-y-4 pt-1">
                                    <div class="space-y-2.5">
                                        <label for="utr" class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            UTR / Transaction ID
                                        </label>
                                        <x-filament::input.wrapper>
                                            <x-filament::input
                                                type="text"
                                                wire:model="utr"
                                                id="utr"
                                                placeholder="e.g. 123456789012"
                                                required
                                            />
                                        </x-filament::input.wrapper>
                                        @error('utr')
                                            <p class="text-xs font-medium text-danger-600 dark:text-danger-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="space-y-2.5">
                                        <label for="proof" class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Payment proof (optional)
                                        </label>
                                        <input
                                            type="file"
                                            id="proof"
                                            wire:model="proof"
                                            accept=".jpg,.jpeg,.png,.pdf"
                                            class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-teal-700 dark:text-gray-300 dark:file:bg-teal-900/40 dark:file:text-teal-200"
                                        />
                                        <div wire:loading wire:target="proof" class="text-xs text-gray-500">Uploading…</div>
                                        @error('proof')
                                            <p class="text-xs font-medium text-danger-600 dark:text-danger-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <x-filament::button
                                        type="submit"
                                        color="primary"
                                        size="sm"
                                        icon="heroicon-m-paper-airplane"
                                    >
                                        <span wire:loading.remove wire:target="submitBankTransfer">Submit for approval</span>
                                        <span wire:loading wire:target="submitBankTransfer">Submitting…</span>
                                    </x-filament::button>

                                    <p class="text-[11px] leading-relaxed text-gray-500 dark:text-gray-400">
                                        Transfer the amount first, then submit UTR. Wallet credits after admin approval.
                                    </p>
                                </form>
                            @endif
                        </div>
                    @endif
                </x-filament::section>
            </div>

            {{-- Transactions (deposits + wallet + earnings) --}}
            <div class="lg:col-span-3">
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <span class="wallet-icon-wrap">
                                <x-filament::icon icon="heroicon-o-clock" class="h-4 w-4 text-teal-600 dark:text-teal-300" />
                            </span>
                            <span class="font-semibold">Transactions</span>
                        </div>
                    </x-slot>

                    <x-slot name="description">
                        Deposits, wallet usage, and commission earnings
                    </x-slot>

                    @if ($recentTransactions->isEmpty())
                        <div class="wallet-empty">
                            <div class="wallet-empty__icon" aria-hidden="true">
                                <x-filament::icon icon="heroicon-o-banknotes" class="h-7 w-7" />
                            </div>
                            <p class="wallet-empty__title">No transactions yet</p>
                            <p class="wallet-empty__hint">
                                Add funds or run recharge APIs — deposits and earnings will show up here.
                            </p>
                        </div>
                    @else
                        {{-- Mobile cards --}}
                        <div class="space-y-3 md:hidden">
                            @foreach ($recentTransactions as $tx)
                                <div class="inspay-ops-card space-y-2">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                                    {{ $tx['title'] }}
                                                </p>
                                                @if ($tx['is_earning'])
                                                    <span class="inspay-ops-badge">Earning</span>
                                                @endif
                                            </div>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 break-words">
                                                {{ $tx['description'] }}
                                            </p>
                                            <p class="mt-1 text-[11px] text-gray-400">
                                                {{ $tx['created_at']->format('M d, Y · h:i A') }}
                                            </p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <p @class([
                                                'font-mono text-sm font-bold',
                                                'text-emerald-600 dark:text-emerald-400' => $tx['is_credit'],
                                                'text-gray-900 dark:text-white' => ! $tx['is_credit'],
                                            ])>
                                                {{ $tx['is_credit'] ? '+' : '-' }}₹{{ number_format($tx['amount'], 2) }}
                                            </p>
                                            <x-filament::badge :color="$tx['status_color']" class="mt-1">
                                                {{ $tx['status'] }}
                                            </x-filament::badge>
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
                                        <th>Details</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentTransactions as $tx)
                                        <tr wire:key="{{ $tx['id'] }}">
                                            <td>
                                                <div class="wallet-table__date">
                                                    {{ $tx['created_at']->format('M d, Y') }}
                                                </div>
                                                <div class="wallet-table__time">
                                                    {{ $tx['created_at']->format('h:i A') }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <div class="wallet-table__date">
                                                        {{ $tx['title'] }}
                                                    </div>
                                                    @if ($tx['is_earning'])
                                                        <span class="inspay-ops-badge">Earning</span>
                                                    @endif
                                                </div>
                                                <div class="wallet-table__ref" title="{{ $tx['description'] }}">
                                                    {{ \Illuminate\Support\Str::limit($tx['description'], 48) }}
                                                </div>
                                                @if ($tx['balance_after'] !== null)
                                                    <div class="wallet-table__time">
                                                        Bal ₹{{ number_format($tx['balance_after'], 2) }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <div @class([
                                                    'wallet-table__amount',
                                                    'text-emerald-600 dark:text-emerald-400' => $tx['is_credit'],
                                                ])>
                                                    {{ $tx['is_credit'] ? '+' : '-' }}₹{{ number_format($tx['amount'], 2) }}
                                                </div>
                                            </td>
                                            <td>
                                                <x-filament::badge :color="$tx['status_color']">
                                                    {{ $tx['status'] }}
                                                </x-filament::badge>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-filament::section>
            </div>
        </div>
    </div>
</x-filament-panels::page>
