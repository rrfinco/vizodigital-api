<x-filament-panels::page>
    <p class="text-sm text-gray-600 dark:text-gray-300 -mt-2">
        Use <code class="rounded bg-gray-100 dark:bg-gray-800 px-1 py-0.5 text-xs font-mono">service_id</code>
        with <code class="rounded bg-gray-100 dark:bg-gray-800 px-1 py-0.5 text-xs font-mono">POST /api/v1/taxation/orders</code>.
        Price is set by admin — do not send amount in the API.
    </p>

    <x-filament::section>
        <x-filament::input.wrapper>
            <x-filament::input
                type="search"
                placeholder="Search service name, category, or ID"
                wire:model.live.debounce.300ms="search"
            />
        </x-filament::input.wrapper>
    </x-filament::section>

    <div class="overflow-x-auto rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
                <tr>
                    <th class="px-3 py-2">Service ID</th>
                    <th class="px-3 py-2">Name</th>
                    <th class="px-3 py-2">Category</th>
                    <th class="px-3 py-2">Price</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach ($this->services() as $service)
                    <tr x-data="{ copied: false }">
                        <td class="px-3 py-2 font-mono font-semibold">{{ $service['id'] }}</td>
                        <td class="px-3 py-2">{{ $service['name'] }}</td>
                        <td class="px-3 py-2 text-xs text-gray-500">{{ $service['category'] }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">₹{{ number_format($service['price'], 2) }}</td>
                        <td class="px-3 py-2">
                            <button
                                type="button"
                                class="text-xs font-medium text-teal-700 hover:text-teal-900 dark:text-teal-300"
                                x-on:click="
                                    navigator.clipboard.writeText({{ \Illuminate\Support\Js::from((string) $service['id']) }}).then(() => {
                                        copied = true;
                                        setTimeout(() => copied = false, 1200);
                                    })
                                "
                            >
                                <span x-show="!copied">Copy</span>
                                <span x-cloak x-show="copied">Copied!</span>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
