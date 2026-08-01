<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-code-bracket" class="h-4 w-4 text-teal-600 dark:text-teal-400" />
                <span class="font-semibold text-sm text-gray-900 dark:text-white">Opcode & SP Key Lookup</span>
            </div>
        </x-slot>

        <div class="space-y-4">
            <!-- Navigation Actions -->
            <div class="flex flex-col gap-2">
                <x-filament::button
                    href="{{ $this->getRechargeOperatorsUrl() }}"
                    tag="a"
                    color="teal"
                    size="xs"
                    icon="heroicon-m-device-phone-mobile"
                    class="w-full justify-start"
                >
                    View All Recharge SP Keys
                </x-filament::button>

                <x-filament::button
                    href="{{ $this->getInspayOperatorsUrl() }}"
                    tag="a"
                    color="gray"
                    size="xs"
                    icon="heroicon-m-queue-list"
                    class="w-full justify-start"
                >
                    View Bill Payment Opcodes
                </x-filament::button>
            </div>

            <!-- Top Operators List -->
            <div class="space-y-2 pt-2 border-t border-gray-100 dark:border-white/5">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 px-0.5">
                    Popular Operators (SP Key)
                </p>

                <div class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach ($this->getPopularShortcodes() as $code)
                        <div class="flex items-center justify-between gap-2 py-2 px-1 text-xs">
                            <div class="flex items-center gap-2 min-w-0">
                                <span @class([
                                    'inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-medium ring-1 ring-inset',
                                    'bg-blue-50 text-blue-700 ring-blue-700/10 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/30' => $code['type'] === 'mobile',
                                    'bg-purple-50 text-purple-700 ring-purple-700/10 dark:bg-purple-400/10 dark:text-purple-400 dark:ring-purple-400/30' => $code['type'] === 'dth',
                                ])>
                                    {{ $code['category'] }}
                                </span>
                                <span class="font-medium text-gray-900 dark:text-white truncate">
                                    {{ $code['name'] }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2 shrink-0" x-data="{ copied: false }">
                                <code class="font-mono text-xs font-bold text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">
                                    {{ $code['sp_key'] }}
                                </code>

                                <button
                                    type="button"
                                    class="text-xs text-gray-500 hover:text-teal-600 dark:text-gray-400 dark:hover:text-teal-300 font-medium transition"
                                    x-on:click="
                                        navigator.clipboard.writeText({{ Js::from($code['sp_key']) }}).then(() => {
                                            copied = true;
                                            setTimeout(() => copied = false, 1200);
                                        })
                                    "
                                >
                                    <span x-show="!copied">Copy</span>
                                    <span x-cloak x-show="copied" class="text-teal-600 dark:text-teal-400 font-bold">Copied!</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
