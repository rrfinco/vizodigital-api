@php
    $fieldId = 'cred-'.uniqid();
@endphp

<div
    x-data="{
        revealed: false,
        copied: false,
        value: @js($value),
        isSecret: @js($secret),
        async copy() {
            try {
                await navigator.clipboard.writeText(this.value);
                this.copied = true;
                setTimeout(() => this.copied = false, 1500);
            } catch (e) {}
        }
    }"
    class="rounded-xl bg-gray-50 px-3 py-2.5 dark:bg-white/5"
>
    <div class="mb-1 flex items-center justify-between gap-2">
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</span>
        <div class="flex items-center gap-1">
            @if ($secret)
                <button
                    type="button"
                    class="rounded-md px-2 py-1 text-[11px] font-semibold text-teal-700 hover:bg-teal-50 dark:text-teal-300 dark:hover:bg-teal-950/40"
                    x-on:click="revealed = !revealed"
                    x-text="revealed ? 'Hide' : 'Show'"
                ></button>
            @endif
            <button
                type="button"
                class="rounded-md px-2 py-1 text-[11px] font-semibold text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-white/10"
                x-on:click="copy()"
                x-text="copied ? 'Copied' : 'Copy'"
            ></button>
        </div>
    </div>
    <code
        class="block break-all font-mono text-xs text-gray-950 dark:text-white"
        x-text="isSecret && !revealed ? '•'.repeat(Math.min(value.length, 24)) : value"
    ></code>
</div>
