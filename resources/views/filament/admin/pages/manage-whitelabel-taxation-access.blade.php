<x-filament-panels::page>
    <x-slot name="heading">
        White-label taxation API access
    </x-slot>

    <x-slot name="description">
        White-label ke liye Taxation API on/off. Partner uske baad apne developers ko enable kar sakta hai. Order pe catalog price float + developer wallet se debit hoti hai — commission nahi.
    </x-slot>

    <div class="space-y-6">
        <div class="flex justify-end">
            <x-filament::button wire:click="save" color="primary" icon="heroicon-m-check">
                Save access
            </x-filament::button>
        </div>

        <div class="overflow-x-auto rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
                    <tr>
                        <th class="px-3 py-2">White-label</th>
                        <th class="px-3 py-2 w-40">Taxation API</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($this->whitelabels() as $wl)
                        <tr>
                            <td class="px-3 py-2 font-medium">{{ $wl->name }}</td>
                            <td class="px-3 py-2">
                                <x-filament::input.wrapper>
                                    <x-filament::input.select wire:model="rows.{{ $wl->id }}.status">
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-3 py-8 text-center text-gray-500">No white-labels found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
