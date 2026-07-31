{{-- Always visible: mobile + desktop. Lives before the user menu (right side). --}}
<div class="crm-topbar-sidebar-toggle">
    <x-filament::icon-button
        color="gray"
        :icon="\Filament\Support\Icons\Heroicon::OutlinedBars3"
        icon-size="lg"
        :label="__('filament-panels::layout.actions.sidebar.expand.label')"
        x-data="{}"
        x-on:click="$store.sidebar.isOpen ? $store.sidebar.close() : $store.sidebar.open()"
        x-bind:aria-expanded="$store.sidebar.isOpen"
        aria-controls="fi-main-sidebar"
        class="crm-topbar-sidebar-toggle-btn"
    />
</div>
