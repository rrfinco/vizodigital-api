@php
    $user = auth()->user();
@endphp

<div class="crm-topbar-chip" title="{{ $label }}">
    <span class="inline-block size-1.5 rounded-full bg-emerald-500"></span>
    <span>{{ $label }}</span>
    @if ($user)
        <span class="opacity-40">·</span>
        <span class="font-medium opacity-80">{{ $user->name }}</span>
    @endif
</div>
