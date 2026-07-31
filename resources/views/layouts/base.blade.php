<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', app(\App\Services\Portal\PortalSettings::class)->name())</title>
    <link rel="icon" href="{{ asset('images/brand/vizo-icon.jpg') }}" type="image/jpeg">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <script>
        (function () {
            const stored = localStorage.getItem('portal-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (!stored && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-full bg-portal-bg text-portal-dark dark:bg-slate-950 dark:text-slate-100" x-data x-init="$store.theme.init()">
    @yield('body')
    @stack('scripts')
</body>
</html>
