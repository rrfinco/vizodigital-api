@extends('layouts.base')

@section('title', 'Sign in — ' . config('portal.name'))

@section('body')
    <div class="flex min-h-screen flex-col justify-center bg-portal-bg px-4 py-12 dark:bg-slate-950">
        <div class="mx-auto w-full max-w-md">
            <div class="mb-8 text-center">
                <a href="{{ route('landing') }}" class="inline-flex items-center gap-2.5">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-primary-600 text-sm font-semibold text-white shadow-card">
                        {{ strtoupper(substr(config('portal.brand.logo_text'), 0, 1)) }}
                    </span>
                    <span class="text-lg font-semibold tracking-tight">{{ config('portal.brand.logo_text') }}</span>
                </a>
                <h1 class="mt-6 text-2xl font-semibold tracking-tight text-portal-dark dark:text-white">Sign in</h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Access your developer portal account</p>
            </div>

            <div class="portal-card p-6 dark:border-slate-800 sm:p-8">
                @if ($errors->any())
                    <div class="mb-4 rounded-2xl border border-portal-danger/30 bg-red-50 px-4 py-3 text-sm text-portal-danger dark:border-red-900/40 dark:bg-red-950/40">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Email</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            class="portal-input"
                            placeholder="you@company.com"
                        >
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Password</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="portal-input"
                            placeholder="••••••••"
                        >
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <input type="checkbox" name="remember" value="1" class="rounded border-portal-border text-primary-600 focus:ring-primary-500">
                        Remember me
                    </label>

                    <button type="submit" class="portal-btn-primary w-full">
                        Sign in
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-sm text-slate-500">
                New developer?
                <a href="{{ route('register') }}" class="font-medium text-primary-600 hover:text-primary-700">Create an account</a>
            </p>
            <p class="mt-3 text-center text-xs text-slate-400">
                Seeded admin: <span class="font-mono">admin@portal.test</span> / <span class="font-mono">password</span>
            </p>
        </div>
    </div>
@endsection
