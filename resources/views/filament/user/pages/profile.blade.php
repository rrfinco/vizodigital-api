@php
    $user = auth()->user();
    $subscription = $user?->activeSubscription();
    $onboarding = $user?->onboarding_status;
@endphp

<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Account details</x-slot>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-white/10">
                    <dt class="text-gray-500 dark:text-gray-400">Name</dt>
                    <dd class="text-right font-medium text-gray-950 dark:text-white">{{ $user?->name ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-white/10">
                    <dt class="text-gray-500 dark:text-gray-400">Email</dt>
                    <dd class="text-right font-medium text-gray-950 dark:text-white">{{ $user?->email ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-white/10">
                    <dt class="text-gray-500 dark:text-gray-400">Email verified</dt>
                    <dd class="text-right font-medium text-gray-950 dark:text-white">
                        {{ $user?->email_verified_at?->timezone(config('app.timezone'))->format('d M Y, h:i A') ?: 'Not verified' }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-white/10">
                    <dt class="text-gray-500 dark:text-gray-400">Company</dt>
                    <dd class="text-right font-medium text-gray-950 dark:text-white">{{ $user?->company_name ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-white/10">
                    <dt class="text-gray-500 dark:text-gray-400">Phone</dt>
                    <dd class="text-right font-medium text-gray-950 dark:text-white">{{ $user?->phone ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-white/10">
                    <dt class="text-gray-500 dark:text-gray-400">Role</dt>
                    <dd class="text-right font-medium text-gray-950 dark:text-white">
                        {{ $user?->getRoleNames()->implode(', ') ?: 'developer' }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">Member since</dt>
                    <dd class="text-right font-medium text-gray-950 dark:text-white">
                        {{ $user?->created_at?->timezone(config('app.timezone'))->format('d M Y, h:i A') ?: '—' }}
                    </dd>
                </div>
            </dl>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Onboarding & KYC</x-slot>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-white/10">
                    <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                    <dd class="text-right font-medium text-gray-950 dark:text-white">
                        {{ $onboarding?->label() ?? (is_string($onboarding) ? $onboarding : '—') }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-white/10">
                    <dt class="text-gray-500 dark:text-gray-400">KYC submitted</dt>
                    <dd class="text-right font-medium text-gray-950 dark:text-white">
                        {{ $user?->kyc_submitted_at?->timezone(config('app.timezone'))->format('d M Y, h:i A') ?: '—' }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-white/10">
                    <dt class="text-gray-500 dark:text-gray-400">Approved at</dt>
                    <dd class="text-right font-medium text-gray-950 dark:text-white">
                        {{ $user?->approved_at?->timezone(config('app.timezone'))->format('d M Y, h:i A') ?: '—' }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-white/10">
                    <dt class="text-gray-500 dark:text-gray-400">Approved by</dt>
                    <dd class="text-right font-medium text-gray-950 dark:text-white">
                        {{ $user?->approvedBy?->name ?: '—' }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">Rejection reason</dt>
                    <dd class="text-right font-medium text-gray-950 dark:text-white">
                        {{ $user?->rejection_reason ?: '—' }}
                    </dd>
                </div>
            </dl>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Wallet</x-slot>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-white/10">
                    <dt class="text-gray-500 dark:text-gray-400">Wallet balance</dt>
                    <dd class="text-right font-medium text-gray-950 dark:text-white">
                        ₹{{ number_format((float) ($user?->wallet_balance ?? 0), 2) }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">Earning balance</dt>
                    <dd class="text-right font-medium text-gray-950 dark:text-white">
                        ₹{{ number_format((float) ($user?->earning_balance ?? 0), 2) }}
                    </dd>
                </div>
            </dl>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Subscription</x-slot>
            @if ($subscription)
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-white/10">
                        <dt class="text-gray-500 dark:text-gray-400">Plan</dt>
                        <dd class="text-right font-medium text-gray-950 dark:text-white">
                            {{ $subscription->plan?->name ?? 'Active plan' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-white/10">
                        <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                        <dd class="text-right font-medium text-gray-950 dark:text-white">
                            {{ ucfirst((string) $subscription->status) }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Valid until</dt>
                        <dd class="text-right font-medium text-gray-950 dark:text-white">
                            {{ $subscription->ends_at?->timezone(config('app.timezone'))->format('d M Y, h:i A') ?: '—' }}
                        </dd>
                    </div>
                </dl>
            @else
                <p class="text-sm text-gray-600 dark:text-gray-300">No active subscription.</p>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
