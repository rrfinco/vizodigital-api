<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Quick links
        </x-slot>

        <x-slot name="description">
            Documentation is public. API usage requires your credentials.
        </x-slot>

        <div class="grid gap-3 sm:grid-cols-2">
            <a
                href="{{ route('docs.overview') }}"
                class="fi-btn relative grid-flow-col items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold outline-none transition duration-75 bg-teal-600 text-white hover:bg-teal-500 focus-visible:ring-2 focus-visible:ring-teal-500/50"
            >
                Open API Docs
            </a>

            <a
                href="{{ url('/') }}"
                class="fi-btn relative grid-flow-col items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold outline-none transition duration-75 bg-gray-100 text-gray-950 hover:bg-gray-200 dark:bg-white/10 dark:text-white dark:hover:bg-white/20"
            >
                Portal Home
            </a>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
