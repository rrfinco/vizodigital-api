@extends('layouts.base')

@section('title', 'Reset password — ' . config('portal.name'))

@section('body')
    <div class="flex min-h-screen flex-col justify-center bg-portal-bg px-4 py-12 dark:bg-slate-950">
        <div class="mx-auto w-full max-w-md">
            <div class="mb-8 text-center">
                <a href="{{ route('landing') }}" class="inline-flex items-center justify-center">
                    <img
                        src="{{ asset(config('portal.brand.logo')) }}"
                        alt="{{ config('portal.brand.logo_text') }}"
                        class="h-10 w-auto object-contain"
                    />
                </a>
                <h1 class="mt-6 text-2xl font-semibold tracking-tight text-portal-dark dark:text-white">Reset password</h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Choose a new password for your account.
                </p>
            </div>

            <div class="portal-card p-6 dark:border-slate-800 sm:p-8">
                @if ($errors->any())
                    <div class="mb-4 rounded-2xl border border-portal-danger/30 bg-red-50 px-4 py-3 text-sm text-portal-danger dark:border-red-900/40 dark:bg-red-950/40">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}">

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Email</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email', $email) }}"
                            required
                            autofocus
                            autocomplete="username"
                            class="portal-input"
                            placeholder="you@company.com"
                        >
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">New password</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="new-password"
                            class="portal-input"
                            placeholder="••••••••"
                        >
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Confirm password</label>
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                            class="portal-input"
                            placeholder="••••••••"
                        >
                    </div>

                    <button type="submit" class="portal-btn-primary w-full">
                        Reset password
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
