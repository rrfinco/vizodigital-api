<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Recent float activity</x-slot>

        @if ($transactions->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">No float ledger entries yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-white/10">
                            <th class="py-2 pr-4 font-medium">When</th>
                            <th class="py-2 pr-4 font-medium">Type</th>
                            <th class="py-2 pr-4 font-medium">Amount</th>
                            <th class="py-2 font-medium">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $tx)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="py-2 pr-4 whitespace-nowrap">{{ $tx->created_at?->format('d M Y H:i') }}</td>
                                <td class="py-2 pr-4">
                                    <span @class([
                                        'inline-flex rounded-md px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset',
                                        'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-400/10 dark:text-emerald-400' => $tx->type === 'credit',
                                        'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-400/10 dark:text-rose-400' => $tx->type !== 'credit',
                                    ])>
                                        {{ strtoupper($tx->type) }}
                                    </span>
                                </td>
                                <td class="py-2 pr-4 font-medium">₹{{ number_format(abs((float) $tx->amount), 2) }}</td>
                                <td class="py-2 text-gray-600 dark:text-gray-300">{{ $tx->description }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
