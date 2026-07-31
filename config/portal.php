<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Developer Portal
    |--------------------------------------------------------------------------
    |
    | Module 0 foundation config. CMS content, versions, and environments
    | will be wired in later modules. Do not hardcode API endpoints here.
    |
    */

    'name' => env('PORTAL_NAME', 'Developer Portal'),

    'tagline' => env('PORTAL_TAGLINE', 'Fast, Secure and Powerful APIs'),

    /*
    | When true (UAT API host), website / docs / onboarding / Filament panels are blocked.
    | Only /api/* (and health/webhook) remain available.
    */
    'api_only' => (bool) env('PORTAL_API_ONLY', false),

    'public_url' => env('PORTAL_PUBLIC_URL', 'https://docs.vizodigital.com'),

    'brand' => [
        'short' => env('PORTAL_BRAND_SHORT', 'API Portal'),
        'logo_text' => env('PORTAL_LOGO_TEXT', 'DevPortal'),
    ],

    'theme' => [
        'default' => env('PORTAL_THEME', 'system'), // light | dark | system
        'primary' => '#2563EB',
        'radius' => '16px',
    ],

    /*
    |--------------------------------------------------------------------------
    | Environments (placeholders — DB-driven from Module 2+)
    |--------------------------------------------------------------------------
    */

    'environments' => [
        'uat' => [
            'label' => 'UAT (Sandbox)',
            'base_url' => env('PORTAL_UAT_BASE_URL', 'https://uat-api.example.com'),
            'badge' => 'Sandbox',
        ],
        'production' => [
            'label' => 'Production',
            'base_url' => env('PORTAL_PRODUCTION_BASE_URL', 'https://api.example.com'),
            'badge' => 'Live',
        ],
    ],

    'default_environment' => env('PORTAL_DEFAULT_ENVIRONMENT', 'uat'),

    /*
    |--------------------------------------------------------------------------
    | Instant search
    |--------------------------------------------------------------------------
    */

    'search' => [
        'min_chars' => 2,
        'limit' => 12,
        'debounce_ms' => 250,
    ],

    /*
    |--------------------------------------------------------------------------
    | Code sample languages (used by snippet generator later)
    |--------------------------------------------------------------------------
    */

    'snippet_languages' => [
        'curl',
        'php',
        'laravel',
        'nodejs',
        'python',
        'java',
        'go',
        'csharp',
        'javascript',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sidebar fallback (used only when no CMS navigation_items exist)
    |--------------------------------------------------------------------------
    */

    'sidebar' => [
        ['label' => 'Overview', 'route' => 'docs.overview', 'icon' => 'home'],
        ['label' => 'Getting Started', 'route' => null, 'icon' => 'rocket'],
        ['label' => 'Authentication', 'route' => null, 'icon' => 'key'],
        ['label' => 'API Explorer', 'route' => null, 'icon' => 'code'],
        ['label' => 'Collections', 'route' => null, 'icon' => 'collection'],
        ['label' => 'Webhook', 'route' => null, 'icon' => 'bolt'],
        ['label' => 'SDK', 'route' => null, 'icon' => 'cube'],
        ['label' => 'Errors', 'route' => null, 'icon' => 'exclamation'],
        ['label' => 'Rate Limits', 'route' => null, 'icon' => 'clock'],
        ['label' => 'Guides', 'route' => null, 'icon' => 'book'],
        ['label' => 'Release Notes', 'route' => null, 'icon' => 'newspaper'],
        ['label' => 'FAQ', 'route' => null, 'icon' => 'question'],
        ['label' => 'Support', 'route' => null, 'icon' => 'support'],
    ],

];
