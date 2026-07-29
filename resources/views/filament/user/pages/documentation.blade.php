<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Public docs</x-slot>
            <x-slot name="description">
                Anyone can read the published documentation. Calling APIs still requires authentication first.
            </x-slot>

            <div class="flex flex-wrap gap-3">
                <x-filament::button tag="a" href="{{ route('docs.overview') }}" target="_blank" rel="noopener noreferrer" color="primary">
                    Open docs
                </x-filament::button>
                <x-filament::button tag="a" href="{{ url('/docs') }}" target="_blank" rel="noopener noreferrer" color="gray">
                    Browse APIs
                </x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Integration checklist</x-slot>
            <ul class="list-disc space-y-2 ps-5 text-sm text-gray-600 dark:text-gray-300">
                <li>Complete signup + KYC, then wait for admin approval</li>
                <li>Read the Authentication guide first (obtain a Bearer token)</li>
                <li>Use UAT client credentials from API Keys while integrating</li>
                <li>Validate webhooks in sandbox</li>
                <li>Ask an admin to unlock Production keys after UAT sign-off</li>
            </ul>
        </x-filament::section>
    </div>
</x-filament-panels::page>
