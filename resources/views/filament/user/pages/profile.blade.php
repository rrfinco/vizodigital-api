@php
    $user = auth()->user();
@endphp

<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Account</x-slot>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-white/10">
                    <dt class="text-gray-500 dark:text-gray-400">Name</dt>
                    <dd class="font-medium text-gray-950 dark:text-white">{{ $user?->name }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-white/10">
                    <dt class="text-gray-500 dark:text-gray-400">Email</dt>
                    <dd class="font-medium text-gray-950 dark:text-white">{{ $user?->email }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">Role</dt>
                    <dd class="font-medium text-gray-950 dark:text-white">
                        {{ $user?->getRoleNames()->implode(', ') ?: 'developer' }}
                    </dd>
                </div>
            </dl>

            <form method="POST" action="{{ filament()->getLogoutUrl() }}" class="mt-6">
                @csrf
                <x-filament::button type="submit" color="danger">
                    Log out
                </x-filament::button>
            </form>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Panel access</x-slot>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                This is the developer CRM panel. Documentation CMS and commission controls live in the admin panel and are not available here.
            </p>
        </x-filament::section>
    </div>
</x-filament-panels::page>
