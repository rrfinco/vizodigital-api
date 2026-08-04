<x-filament-panels::page>
    @php
        $wl = $this->getWhitelabel();
    @endphp

    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Current wallet balance</x-slot>
            <p class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                ₹{{ number_format((float) ($wl?->wallet_balance ?? 0), 2) }}
            </p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                When this balance is empty, your developers’ APIs return service unavailable — even if their wallets have balance.
            </p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Request wallet top-up</x-slot>
            <x-slot name="description">Submit bank transfer details. Admin will approve and credit your wallet.</x-slot>

            <form wire:submit.prevent="submitFloatRequest" class="space-y-4 max-w-xl">
                <div>
                    <label class="block text-sm font-medium mb-1.5">Amount (₹)</label>
                    <x-filament::input.wrapper>
                        <x-filament::input type="number" step="0.01" min="1" wire:model="amount" />
                    </x-filament::input.wrapper>
                    @error('amount') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5">UTR / Reference</label>
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" wire:model="utr" />
                    </x-filament::input.wrapper>
                    @error('utr') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5">Payment proof (optional)</label>
                    <input type="file" wire:model="proof" accept=".jpg,.jpeg,.png,.pdf" class="block w-full text-sm" />
                    @error('proof') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                    <div wire:loading wire:target="proof" class="text-xs text-gray-500 mt-1">Uploading…</div>
                </div>

                <x-filament::button type="submit" color="primary" icon="heroicon-m-paper-airplane">
                    Submit request
                </x-filament::button>
            </form>
        </x-filament::section>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">Recent requests</x-slot>
                @php $requests = $this->getRecentRequests(); @endphp
                @if ($requests->isEmpty())
                    <p class="text-sm text-gray-500">No requests yet.</p>
                @else
                    <ul class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($requests as $req)
                            <li class="py-3 flex items-center justify-between gap-3 text-sm">
                                <div>
                                    <p class="font-medium">₹{{ number_format((float) $req->amount, 2) }}</p>
                                    <p class="text-gray-500">{{ $req->utr }} · {{ $req->created_at?->format('d M Y') }}</p>
                                </div>
                                <span class="uppercase text-xs font-semibold tracking-wide">{{ $req->status }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Wallet transactions</x-slot>
                @php $ledger = $this->getRecentLedger(); @endphp
                @if ($ledger->isEmpty())
                    <p class="text-sm text-gray-500">No wallet transactions yet.</p>
                @else
                    <ul class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($ledger as $tx)
                            <li class="py-3 text-sm">
                                <div class="flex justify-between gap-3">
                                    <span class="font-medium {{ $tx->type === 'credit' ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $tx->type === 'credit' ? '+' : '−' }}₹{{ number_format(abs((float) $tx->amount), 2) }}
                                    </span>
                                    <span class="text-gray-500">{{ $tx->created_at?->format('d M Y H:i') }}</span>
                                </div>
                                <p class="text-gray-600 dark:text-gray-300 mt-0.5">{{ $tx->description }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
