@props(['value'])

@php
    $encoded = is_string($value)
        ? $value
        : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp

<pre class="overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs leading-relaxed text-slate-100 dark:bg-black"><code>{{ $encoded }}</code></pre>
