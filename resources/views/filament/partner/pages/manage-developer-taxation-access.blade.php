<x-filament-panels::page>
    <x-slot name="heading">
        Developer taxation API access
    </x-slot>

    <x-slot name="description">
        Apne developers ke liye Taxation API on/off. Order confirm pe admin catalog price unke wallet (aur aapke float) se debit hoti hai. Commission nahi hai.
    </x-slot>

    <div class="space-y-6">
        @if (! $this->isWhitelabelEnabled())
            <div class="rounded-xl bg-amber-50 p-4 border border-amber-200 text-sm text-amber-800 dark:bg-amber-950/20 dark:border-amber-900/50 dark:text-amber-300">
                Taxation API aapke white-label pe enable nahi hai. Pehle platform admin ko bolo.
            </div>
        @endif

        <div class="flex justify-end">
            <x-filament::button wire:click="save" color="primary" icon="heroicon-m-check">
                Save access
            </x-filament::button>
        </div>

        <div class="overflow-x-auto rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
                    <tr>
                        <th class="px-3 py-2">Developer</th>
                        <th class="px-3 py-2">Email</th>
                        <th class="px-3 py-2 w-40">Taxation API</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($this->developers() as $user)
                        <tr>
                            <td class="px-3 py-2 font-medium">{{ $user->company_name ?: $user->name }}</td>
                            <td class="px-3 py-2 text-gray-500">{{ $user->email }}</td>
                            <td class="px-3 py-2">
                                <x-filament::input.wrapper>
                                    <x-filament::input.select wire:model="rows.{{ $user->id }}.status">
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-3 py-8 text-center text-gray-500">No developers under your white-label yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
