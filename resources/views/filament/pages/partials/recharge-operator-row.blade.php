@php
    $isActive = ($op['status'] ?? 'Active') === 'Active';
    $commission = $op['commission_percentage'] ?? '0.00';
    $isMobile = ($op['type'] ?? '') === 'mobile';
@endphp

<div
    wire:key="rc-op-{{ $op['type'] }}-{{ $op['sp_key'] }}"
    class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 flex flex-col justify-between gap-3.5 transition duration-150 hover:ring-gray-950/10 dark:hover:ring-white/20"
>
    <!-- Operator Info Header -->
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <div class="relative shrink-0">
                <img
                    src="{{ $this->operatorLogoSrc($op) }}"
                    onerror="this.onerror=null;this.src='{{ asset($op['logo_path']) }}';"
                    alt="{{ $op['operator_name'] }}"
                    class="h-10 w-10 rounded-lg border border-gray-200 bg-gray-50 p-1 dark:border-white/10 dark:bg-gray-800 object-contain"
                    loading="lazy"
                />
            </div>

            <div class="min-w-0 flex-1">
                <h4 class="truncate text-sm font-semibold text-gray-900 dark:text-white" title="{{ $op['operator_name'] }}">
                    {{ $op['operator_name'] }}
                </h4>

                <div class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <span @class([
                        'inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-medium ring-1 ring-inset',
                        'bg-blue-50 text-blue-700 ring-blue-700/10 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/30' => $isMobile,
                        'bg-purple-50 text-purple-700 ring-purple-700/10 dark:bg-purple-400/10 dark:text-purple-400 dark:ring-purple-400/30' => ! $isMobile,
                    ])>
                        {{ $isMobile ? 'Mobile' : 'DTH' }}
                    </span>

                    <span @class([
                        'inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-medium ring-1 ring-inset',
                        'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/30' => $isActive,
                        'bg-gray-100 text-gray-600 ring-gray-500/10 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700' => ! $isActive,
                    ])>
                        {{ $isActive ? 'Active' : 'Inactive' }}
                    </span>

                    @if ((float) $commission > 0)
                        <span class="inline-flex items-center rounded-md bg-teal-50 px-1.5 py-0.5 text-[10px] font-medium text-teal-700 ring-1 ring-inset ring-teal-700/10 dark:bg-teal-400/10 dark:text-teal-300 dark:ring-teal-400/30">
                            {{ $commission }}% Comm.
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- SP Key & Copy Footer -->
    <div class="flex items-center justify-between gap-2 pt-2.5 border-t border-gray-100 dark:border-white/5">
        <div class="flex items-center gap-2">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">SP Key:</span>
            <code class="rounded-md bg-gray-100 dark:bg-gray-800 px-2 py-0.5 font-mono text-xs font-bold text-gray-900 dark:text-gray-100">
                {{ $op['sp_key'] }}
            </code>
        </div>

        <button
            type="button"
            class="inline-flex items-center gap-1 rounded-md bg-gray-50 px-2.5 py-1 text-xs font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-gray-700 transition"
            x-data="{ copied: false }"
            x-on:click="
                navigator.clipboard.writeText({{ Js::from((string) $op['sp_key']) }}).then(() => {
                    copied = true;
                    $wire.copySpKey({{ (int) $op['sp_key'] }});
                    setTimeout(() => copied = false, 1500);
                })
            "
        >
            <span x-show="!copied" class="inline-flex items-center gap-1">
                <x-filament::icon icon="heroicon-m-clipboard-document" class="h-3.5 w-3.5 text-gray-400" />
                Copy
            </span>
            <span x-cloak x-show="copied" class="inline-flex items-center gap-1 text-teal-600 dark:text-teal-400 font-bold">
                <x-filament::icon icon="heroicon-m-check" class="h-3.5 w-3.5" />
                Copied
            </span>
        </button>
    </div>
</div>
