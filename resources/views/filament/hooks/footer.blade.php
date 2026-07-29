<footer class="fi-footer">
    <div class="crm-footer-bar">
        <div>
            &copy; {{ now()->year }} {{ config('portal.name') }}
            <span class="opacity-50">·</span>
            {{ $label }}
        </div>

        <div class="crm-footer-links">
            <a href="{{ route('docs.overview') }}" target="_blank" rel="noopener noreferrer">Docs</a>
            <a href="{{ url('/') }}">Portal</a>
            <a href="{{ route('landing') }}">Home</a>
        </div>
    </div>
</footer>
