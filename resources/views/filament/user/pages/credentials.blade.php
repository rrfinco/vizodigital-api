@php
    $panels = $this->getCredentialPanels();
@endphp

<x-filament-panels::page>
    <p class="mb-6 text-sm text-gray-600 dark:text-gray-300">
        Use UAT keys while integrating. Production keys appear here after an admin unlocks live access.
    </p>

    <div class="grid gap-6 lg:grid-cols-2">
        @forelse ($panels as $panel)
            @php
                /** @var \App\Models\ApiEnvironment $environment */
                $environment = $panel['environment'];
                /** @var \App\Models\ApiCredential|null $credential */
                $credential = $panel['credential'];
                $usable = $credential?->status->isUsable() ?? false;
                $slug = $environment->slug instanceof \BackedEnum ? $environment->slug->value : (string) $environment->slug;
            @endphp

            <x-filament::section>
                <x-slot name="heading">
                    <span class="inline-flex items-center gap-2">
                        {{ $environment->label ?: $environment->name }}
                        @if ($environment->badge)
                            <span @class([
                                'rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide',
                                'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-200' => $slug === 'uat',
                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200' => $slug === 'production',
                            ])>
                                {{ $environment->badge }}
                            </span>
                        @endif
                    </span>
                </x-slot>

                <x-slot name="description">
                    Base URL: {{ $panel['base_url'] }}
                </x-slot>

                @if (! $credential)
                    <div class="rounded-xl bg-gray-50 px-4 py-5 text-sm text-gray-600 dark:bg-white/5 dark:text-gray-300">
                        @if ($slug === 'production')
                            Production keys are not issued yet. Finish UAT testing, then ask an admin to unlock live access.
                        @else
                            No UAT credentials assigned yet. Contact an admin to provision your sandbox keys.
                        @endif
                    </div>
                @elseif (! $usable)
                    <div class="space-y-3">
                        <div class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-950/50 dark:text-amber-200">
                            {{ $credential->status->label() }}
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ $credential->notes ?: 'These keys are not active yet. An admin must approve them before you can call APIs.' }}
                        </p>
                    </div>
                @else
                    <div class="space-y-3">
                        <div class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200">
                            {{ $credential->status->label() }}
                        </div>

                        @include('filament.user.partials.credential-field', [
                            'label' => 'Client ID',
                            'value' => $credential->client_id,
                            'secret' => false,
                        ])

                        @include('filament.user.partials.credential-field', [
                            'label' => 'API Secret',
                            'value' => $credential->api_secret,
                            'secret' => true,
                        ])

                        @include('filament.user.partials.credential-field', [
                            'label' => 'Base URL',
                            'value' => $panel['base_url'],
                            'secret' => false,
                        ])
                    </div>
                @endif
            </x-filament::section>
        @empty
            <x-filament::section>
                <x-slot name="heading">No environments</x-slot>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    UAT / Production environments are not configured yet.
                </p>
            </x-filament::section>
        @endforelse
    </div>
</x-filament-panels::page>
