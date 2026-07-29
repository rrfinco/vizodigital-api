@php
    $user = auth()->user();
@endphp

<div class="crm-sidebar-footer">
    @if ($user)
        <div class="font-semibold text-slate-700 dark:text-slate-200">{{ $user->name }}</div>
        <div class="truncate">{{ $user->email }}</div>
    @else
        <div>Signed out</div>
    @endif
</div>
