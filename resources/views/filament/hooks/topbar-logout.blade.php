@auth
    <form method="POST" action="{{ filament()->getLogoutUrl() }}" class="ms-2">
        @csrf
        <button
            type="submit"
            class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 dark:border-red-500/30 dark:bg-red-950/40 dark:text-red-200 dark:hover:bg-red-950/70"
        >
            Log out
        </button>
    </form>
@endauth
