@extends('layouts.base')

@section('title', 'Forgot password — ' . config('portal.name'))

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
                <h1 class="mt-6 text-2xl font-semibold tracking-tight text-portal-dark dark:text-white">Forgot password</h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Enter your email and we will send a reset link.
                </p>
            </div>

            <div class="portal-card p-6 dark:border-slate-800 sm:p-8">
                @if (session('status'))
                    <div class="mb-4 rounded-2xl border border-emerald-300/40 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-300">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-2xl border border-portal-danger/30 bg-red-50 px-4 py-3 text-sm text-portal-danger dark:border-red-900/40 dark:bg-red-950/40">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
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

                    <button type="submit" class="portal-btn-primary w-full">
                        Email password reset link
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-sm text-slate-500">
                Remembered your password?
                <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:text-primary-700">Back to sign in</a>
            </p>
        </div>
    </div>
@endsection
